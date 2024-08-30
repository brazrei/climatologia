class API {
    tipoMsg
    options
    arrResposta
    apiKey
    proxy = 'true'
    constructor(tipo, key) {
        this.apiKey = key
        this.tipoMsg = tipo
        this.options = { criarCSV: false, mesesSelecionados: false }
    }

    setOptions(options) {
        this.options = options
    }

    getOptions() {
        return this.options
    }

    getDate(d) {
        let ano = d.substr(0, 4)
        let mes = d.substr(4, 2) - 1
        let dia = d.substr(6, 2)
        return new Date(ano, mes, dia)
    }

    getProxy() {
        return this.proxy
    }

    incDay(d) {
        return d.setDate(d.getDate() + 1)

    }

    fillZero(n) {
        return parseInt(n) < 10 ? "0" + n : "" + n
    }

    dateToStr(d) {
        return d.getFullYear() + this.fillZero(d.getMonth() + 1) + this.fillZero(d.getDate())
    }

    intervalToArray(di, df, urlData) {
        function makeArrayDiDf(di, df, arr) {
            let arrUrl = []
            let idi, idf
            arr.forEach((e, idx) => {
                if (idx == 0) {
                    idi = di
                    idf = arr[1] + "00"
                } else {
                    idi = e + "00"
                    if (idx < arr.length - 1)
                        idf = arr[idx + 1] + "00"
                    else
                        idf = df
                }
                arrUrl.push({ di: idi, df: idf })

            })
            return arrUrl
        }

        function makeArrayUrl(arrDias, urlData) {
            let r = []
            arrDias.forEach(e => {
                r.push(`/WebServiceOPMET/getMetarOPMET.php?local=${urlData.localidade}&msg=${urlData.tipoMsg}&data_ini=${e.di}&data_fim=${e.df}&proxy=${urlData.proxy}`)
            })
            return r
        }

        let r = []
        let xdf = df
        df = this.getDate(df)
        let next = this.getDate(di)
        r.push(this.dateToStr(new Date(next)))
        next = new Date(this.incDay(next))

        while (next <= df) {
            r.push(this.dateToStr(new Date(next)))
            next = new Date(this.incDay(next))
        }
        if (r.length == 1)
            r.push(this.dateToStr(new Date(df)))

        return makeArrayUrl(makeArrayDiDf(di, xdf, r), urlData)

    }


    getApiKey() {
        return this.apiKey
    }

    getOpmet(callBack, localidade, datai, dataf) {

        async function getPages(arrDias, pg, callBack, arr, obj) {

            for (let i = pg; i <= arrDias.length; i++) {
                const response = await fetch(arrDias[i]);
                let data = await response.text();
                let r
                r = data.split("=")
                r = r.splice(0, r.length - 1)
                r.map((mens) => (

                    arr = arr.concat([{ mens: mens.replace('\n', '') + "=" }])
                )
                )
                if (data.includes("*#*Erro na consulta"))
                    i--
            }
            obj.arrResposta.concat(arr) //copia o array
            callBack(arr, obj.tipoMsg, obj.options)
        }

        function getUrlPage(arrDias, pg) {//'/?page_tam=150&data_ini=2023030900&data_fim=2023031005&page=2'
            //return url.split("page=")[0] + "page="

            return arrDias[pg]

        }
        let dataIni = datai, dataFim = dataf
        let resp = []

        let arrDias = this.intervalToArray(datai, dataf, { localidade, tipoMsg: this.tipoMsg.toLowerCase(), proxy: this.getProxy() })
        let numPages = arrDias.length
        let paginaAtual = 0
        //let urlBase = `/WebServiceOPMET/getMetarOPMET.php?local=${localidade}&msg=${this.tipoMsg.toLowerCase()}&data_ini=${dataIni}&data_fim=${dataFim}&proxy=true`
        //let urlBase = `https://api-redemet.decea.mil.br/mensagens/${this.tipoMsg.toLowerCase()}/${localidade}?api_key=${this.getApiKey()}&data_ini=${dataIni}&data_fim=${dataFim}`
        let urlBase = getUrlPage(arrDias, paginaAtual)
        fetch(urlBase)
            .then((response) => {
                if (response.ok)
                    return response.text()
            })
            .then(response => {
                this.arrResposta = []
                let r
                r = response.split("=")
                r = r.splice(0, r.length - 1)
                r.map((mens) => (

                    resp = resp.concat([{ mens: mens.replace('\n', '') + "=" }])
                )
                )

                if (numPages > 1) {
                    //if (false) {
                    paginaAtual++
                    //let urlPage = getUrlPage(arrDias, paginaAtual)
                    getPages(arrDias, paginaAtual, callBack, resp, this)
                }
                else {
                    this.arrResposta.concat(resp)
                    callBack(resp, this.tipoMsg, this.options)
                }



            })
    }
    gerarUrlsBase(tipoMsg, localidade, apiKey, dataIni, dataFim) {
        let urls = [];
        let urlBase = `https://api-redemet.decea.mil.br/mensagens/${tipoMsg.toLowerCase()}/${localidade}?api_key=${apiKey}`;

        // Convertendo as strings de data para objetos de data
        let startDate = new Date(dataIni);
        let endDate = new Date(dataFim);

        // Verifica se o intervalo é maior do que 6 meses
        while (startDate < endDate) {
            let endSegment = new Date(startDate);
            endSegment.setMonth(endSegment.getMonth() + 6); // Avançar 6 meses

            // Se o final do segmento for depois do final do intervalo, ajusta para a data final
            if (endSegment > endDate) {
                endSegment = endDate;
            }

            // Formata as datas no formato necessário (YYYYMMDD)
            let dataIniFormatted = startDate.toISOString().slice(0, 10).replace(/-/g, '');
            let dataFimFormatted = endSegment.toISOString().slice(0, 10).replace(/-/g, '');

            // Cria a URL para o segmento atual
            let url = `${urlBase}&data_ini=${dataIniFormatted}00&data_fim=${dataFimFormatted}23`;
            urls.push(url);

            // Avança para o próximo segmento: um dia após o final do segmento atual
            startDate = new Date(endSegment);
            startDate.setDate(startDate.getDate() + 1);
        }

        return urls;
    }

    async fetchDataFromUrls( callBack, arrayUrlsBase) {
        this.arrResposta = []

        for (let urlBase of arrayUrlsBase) {
            const response = await fetch(urlBase)
            if (!response.ok)
                throw new Error(`Erro na consulta: ${response.statusText}`);

            const responseData = await response.json();

            if (!responseData.status) {
                alert("Erro na Consulta de Mensagens! " + responseData.message);
                return;
            }

            //cria um array contendo as mensagens em objetos

            if (!response.status) {
                alert("Erro na Consulta de Mensagens! " + response.message)
                return
            }
            let resp = [];

            responseData.data.data.map((mens) => (
                resp = resp.concat([mens])
            ))

            if (responseData.data.last_page > 1) {
                let urlPages = this.getUrlPages(responseData.data.next_page_url)
                await this.getPages(urlBase + urlPages, responseData.data.last_page, callBack, resp)
            }
            else {
                this.arrResposta = this.arrResposta.concat(resp)
            }

        }

    }
    async getPages(url, lastPage, callBack, arg) {

        for (let i = 2; i <= lastPage; i++) {
            const response = await fetch(url + i);
            let { data, total_pages } = await response.json();
            data.data.forEach(mens => arg = arg.concat([mens]))
        }
        this.arrResposta = this.arrResposta.concat(arg) //copia o array
        //callBack(arg, obj.tipoMsg, obj.options)
    }
    getUrlPages(url) {//'/?page_tam=150&data_ini=2023030900&data_fim=2023031005&page=2'
        return url.split("page=")[0] + "page="

    }
    async getRedemet(callBack, localidade, datai, dataf, options) {



        let dataIni = datai, dataFim = dataf
        let resp = []
        arrayCSV = []
        this.setOptions(options)

        let arrayUrlsBase = this.gerarUrlsBase(this.tipoMsg.toLowerCase(), localidade, this.getApiKey(), dataIni, dataFim)
        //let urlBase = `https://api-redemet.decea.mil.br/mensagens/${this.tipoMsg.toLowerCase()}/${localidade}?api_key=${this.getApiKey()}&data_ini=${dataIni}00&data_fim=${dataFim}00`

        await this.fetchDataFromUrls( callBack, arrayUrlsBase)

        callBack(this.getResposta(), this.tipoMsg, this.getOptions())

    }


    getResposta() {
        if (Array.isArray(this.arrResposta))
            return this.arrResposta
        else
            return false
    }
    arraySize(arr) {
        return arr.length
    }
}

class METAR extends API {

    constructor(apiKey) {
        super("METAR", apiKey);
    }

    getLocalidade(metar) {
        var campos = [];

        var idxLoc = 1;
        if (metar.indexOf(" COR ") > 0) {
            idxLoc = idxLoc + 1;
        }

        campos = metar.split(" ");

        return campos[idxLoc];
    }

}

class SIGMET extends API {

    constructor(apiKey) {
        super("SIGMET", apiKey);
    }
}

class AIRMET extends API {

    constructor(apiKey) {
        super("AIRMET", apiKey);
    }
}

class TAF extends API {

    constructor(apiKey) {
        super("TAF", apiKey);
    }

    getLocalidade(taf) {
        var campos = [];

        var idxLoc = 1;
        if (taf.indexOf(" COR ") > 0) {
            idxLoc = idxLoc + 1;
        }

        campos = taf.split(" ");

        return campos[idxLoc];
    }

}

class GAMET extends API {

    constructor(apiKey) {
        super("GAMET", apiKey);
    }

    getLocalidade(gamet) {
        var campos = [];

        var idxLoc = 1;
        if (gamet.indexOf(" COR ") > 0) {
            idxLoc = idxLoc + 1;
        }

        campos = gamet.split(" ");

        return campos[idxLoc];
    }

}

class AVISO extends API {

    constructor(apiKey, tipo = "AVISO") {
        super(tipo, apiKey);
    }

}
