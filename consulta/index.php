<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtividade CIMAER</title>
    <script src="js/scripts.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.3.min.js" integrity="sha256-pvPw+upLPUjgMXY0G+8O0xUf+/Im1MZjXxxgOcBQBXU=" crossorigin="anonymous"></script>


    <link rel="stylesheet" href="css/styles.css">

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://code.jquery.com/jquery-3.6.3.min.js" integrity="sha256-pvPw+upLPUjgMXY0G+8O0xUf+/Im1MZjXxxgOcBQBXU=" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
    <script src=" https://cdn.jsdelivr.net/npm/js-cookie@3.0.1/dist/js.cookie.min.js "></script>

    <script src="//cdn.jsdelivr.net/npm/gasparesganga-jquery-loading-overlay@1.6.0/src/loadingoverlay.min.js"></script>

</head>

<body>
<?php echo "<p>2123</p>";?>

    <script>
        globalMesRef = <?php if (isset($_GET["mesref"])) echo '"'.$_GET["mesref"].'"'."\n"; ?>
        globalTipoMsg = <?php if (isset($_GET["tipomsg"])) echo '"'.$_GET["tipomsg"].'"'."\n"; else echo "false\n";?>

        fillZero = (new API()).fillZero

        function getDataIni(data) {
            return `${data.getFullYear()}${fillZero(data.getMonth() + 1)}${fillZero(data.getDate())}01`
        }

        function getDataFim(data) {
            let lastDay = new Date(data.getFullYear(), data.getMonth() + 2, 0)
            let ano = data.getFullYear()
            if (data.getMonth() == 11)
                ano++
            return `${ano}${fillZero(lastDay.getMonth() + 1)}${fillZero(data.getDate())}00`
            //console.log (  `${data.getFullYear()}${fillZero(lastDay.getMonth() + 1)}${fillZero(data.getDate())}00`)
        }

        $(document).ready(function() {

            auxmes = globalMesRef.split("-")
            globalDataIni = getDataIni(new Date(`${auxmes[0]}/01/${auxmes[1]}`))
            globalDataFin = getDataFim(new Date(`${auxmes[0]}/01/${auxmes[1]}`))

            //consultaProdutividade(2,globalTipoMsg)

        })
    </script>
</body>

</html>