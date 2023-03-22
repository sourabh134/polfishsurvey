<html>
  <head>
    <title>Pollfish API Offerwall Example</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.3/jquery.min.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  </head>
  <body>
  <style>
    
    .text-center {
      text-align: center;
    }
    .container {
      max-width: 960px;
      margin: 0 auto;
    }
    button {
      outline: none;
      border: none;
      background-color: #0e68f9;
      padding: 8px 14px;
      border-radius: 6px;
      color: white;
      font-size: 14px;
      cursor: pointer;
    }

    iframe {
      width: 100%;
      height: 100%;
    }
    span#loadingMessage {
        margin: 20% 30% 30% 30%;
    }
    #survey{display:none;}
    div#js-offer-rewards {
        display: none;
    }
    div#pollfishIndicator {
        display: none !important;
    }
  </style>
  <div>
  	<div class="container">
  		<span id='loadingMessage' style="display:none;"><img src="./upload/img/loadergif.gif"/></span>
  	</div>
    <div class="container text-center hide1" style="margin-top:150px">
            <?php if($check==0){ ?>
                <h2 class="hide1">Tell Us what you think </h2>
                <p class="hide1">Hey there! We're doing some research on future features and are curious to hear your thoughts. </p>
                <div class="form-group text-center hide1">
                    <button id="requestSurvey" onclick="fetchSurvey()">Take the survey</button>
    	<button id="buttonRewardedSurvey" onclick="openSurvey()" style="display:none"></button>
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
  <div id='surveyContainer'>
  	<div id="survey">
  		<iframe id="pollfishSurveyFrame" frameborder="0" name="pollfishSurveyFrame" seamless="seamless"></iframe>
  	</div>
  </div>
  
  <script>

    /**
    * This function makes a call to the api in order to fetch the survey
    */
    function fetchSurvey(){    	
      $('.hide1').css('display','none')
    	hideElement('requestSurvey');
    	showElement('loadingMessage');
    	// const url = `https://wss.pollfish.com/v2/device/register/true?json=%7B%22api_key%22%3A%22${api_key}%22%2C%22offerwall%22%3A%22true%22%2C%22debug%22%3A%22${debug}%22%2C%22ip%22%3A%221.2.3.4%22%2C%22device_id%22%3A%22${device_id}%22%2C%22timestamp%22%3A%221517312061131%22%2C%22encryption%22%3A%22NONE%22%2C%22version%22%3A%229%22%2C%22device_descr%22%3A%22UNKNOWN%22%2C%22os%22%3A%223%22%2C%22os_ver%22%3A%2210.13.2%22%2C%22scr_h%22%3A%221178%22%2C%22src_w%22%3A%221920%22%2C%22scr_size%22%3A%2223.46429949294128%22%2C%22manufacturer%22%3A%22UNKNOWN%22%2C%22locale%22%3A%22en-US%2Cen%2Cel%22%2C%22request_uuid%22%3A%22${request_uuid}%22%2C%22hardware_accelerated%22%3A%22false%22%2C%22video%22%3A%22true%22%2C%22survey_format%22%3A%220%22%7D&dontencrypt=true&webplugin=false&iframewidth=400px&position=BOTTOM_RIGHT` ;
    	const url = `<?=$url?>` ;
    	const oReq = new XMLHttpRequest();
    	oReq.addEventListener("load", function () {
    		hideElement('loadingMessage');
        //something went wrong
    		if (this.status === '400' || this.status === '500') {
    			showAndAfterHide('errorMessage', 3000);
    			showElement('requestSurvey');
    		}
        //load the survey into an iframe
    		var ifrm = document.getElementById('pollfishSurveyFrame');
    		ifrm.src = url;
        showElement('survey');
    	});

    	oReq.open("GET", url);
    	oReq.send();
        var survey_id="<?=$survey_id?>";
        var monitor_id="<?=$monitor_id?>";
        var urls="<?=$url?>";
        var json="<?=$json?>";
        var request_uuids="<?=$request_uuid?>";
        var click_id="<?=$click_id?>";
        var token="<?=csrf_token()?>"
        $.ajax({
            type:'post',
            url:'{{url("/survey-save")}}',
            data:{survey_id:survey_id,monitor_id:monitor_id,url:urls,_token:token,json:json,request_uuid:request_uuids,click_id:click_id},
            success:function(data){
                //console.log(data);
                
            }
        })
    }

    /**
    * This function is fired when the user has selected to open survey (e.g in order to unlock some feature)
    */
    function openSurvey () {
    	showElement('survey');
    	removeElement('rewardText');
    	hideElement('buttonRewardedSurvey');
    }

    var coins;
    ////////////////////////////////////////////////////

    ////////////////// Helper Methods //////////////////
    function showAndAfterHide(idOfElementToShow, timeToShow) {
    	var elem = document.getElementById(`${idOfElementToShow}`);
    	elem.style.display = 'block';
    	setTimeout(function(){ elem.style.display='none' }, timeToShow);
    }

    function showElement(idOfElementToShow) {
    	document.getElementById(`${idOfElementToShow}`).style.display = 'block';
    }

    function hideElement(idOfElementToHide) {
    	document.getElementById(`${idOfElementToHide}`).style.display = 'none';
    }

    function removeElement(idOfElemToRemove) {
    	var elem = document.querySelector(`#${idOfElemToRemove}`);
    	elem.parentNode.removeChild(elem);
    }

    function createElementAndAppendTo(idOfElementToAppend, typeOfElement, idOfNewElement, text) {
    	var newElem = document.createElement(`${typeOfElement}`)
    	newElem.setAttribute("id", `${idOfNewElement}`);
    	newElem.innerHTML = `${text}`;
    	document.getElementById(`${idOfElementToAppend}`).appendChild(newElem);
    }
  	////////////////// End of Helper Methods //////////////////
  </script>
  <script>
    var pollfishConfig = {
        api_key: "<?=$APIkey?>",
        user_id: "<?=base64_decode($monitor_id)?>",
        debug: true,
        offerwall: false,
        request_uuid: "<?=base64_decode($request_uuid)?>",
        closeCallback: customSurveyClosed,
        userNotEligibleCallback: customUserNotEligible,
        closeAndNoShowCallback: customCloseAndNoShow,
        surveyCompletedCallback: customSurveyFinished,
      };



function customSurveyClosed(){
  console.log("user closed the survey");
  window.location.href=`<?=url('/survey_closed')?>`;
}

function customUserNotEligible(){
  console.log("user is not eligible");
  window.location.href=`<?=url('/survey_noteligible')?>`;

}

function customSurveyFinished(data){
  window.location.href=`<?=url('/survey_completed')?>`;
}

function customCloseAndNoShow(){
  console.log("close and hide the pollfish panel");
}

  </script>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
  <script src="https://storage.googleapis.com/pollfish_production/sdk/webplugin/pollfish.min.js"></script>
  </body>
</html>
