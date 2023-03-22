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
            <?php if($check==0){ ?>
                <h2>Tell Us what you think </h2>
                <p>Hey there! We're doing some research on future features and are curious to hear your thoughts. </p>
                <div class="form-group text-center">
                    <button class="btn btn-success" id="survey_start">Take the survey</button>
                </div>
            <?php }else if($check==1){ ?>
                <h2>Thank you for taking this survey </h2>
                <p>Sorry! You already answered this survey</p>
            <?php }else{ ?>
                <h2>Thank you for taking this survey </h2>
                <p>Sorry! You are not eligible to answered this survey</p>
            <?php } ?>
            
            
        </div>
        
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <script>
        window.onload = function () {
            var popup = document.getElementById("popup");
            popup.classList.remove("hidden");
            setTimeout(()=>popup.classList.add("fade-in")); 
            startTimer();       
        };
    </script>
    <script>
        $('#survey_start').click(function(){
            var survey_id="<?=$survey_id?>";
            var monitor_id="<?=$monitor_id?>";
            var url="<?=$url?>";
            var json="<?=$json?>";
            var request_uuid="<?=$request_uuid?>";
            var click_id="<?=$click_id?>";
            var token="<?=csrf_token()?>"
            $.ajax({
                type:'post',
                url:'{{url("/survey-save")}}',
                data:{survey_id:survey_id,monitor_id:monitor_id,url:url,_token:token,json:json,request_uuid:request_uuid,click_id:click_id},
                success:function(data){
                    //console.log(data);
                    if(data==1){
                        window.location.href=url;
                    }
                }
            })
        })
    </script>
    
    
    </body>
</html>