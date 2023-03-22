<html>
    <head>
        <title>Survey</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
        <style>
            #popup {
            display: inline-block;
            opacity: 0;
            position: fixed;
            top: 20%;
            left: 50%;
            padding: 1em;
            transform: translateX(-50%);
            background: #fff;
            border: 1px solid #888;
            box-shadow: 1px 1px .5em 0 rgba(0, 0, 0, .5);
            transition: opacity .3s ease-in-out;
            }

            #popup.hidden {
            display: none;
            }
            #popup.fade-in {
            opacity: 1;
            }
        </style>
    </head>
    <body>
    <div id = "popup" class = "hidden">
        <div>
            <h2>Survey Closed </h2>
            <p>Thank you for taking the time for this survey</p>
            
        </div>        
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <script>
        window.onload = function () {
            var popup = document.getElementById("popup");
            popup.classList.remove("hidden");
            setTimeout(()=>popup.classList.add("fade-in"));        
        };
    </script>    
    </body>
</html>