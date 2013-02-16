
var req;
 
function loadXMLDoc(url,params)
{
    req = null;
    if (window.XMLHttpRequest) {
            req = new XMLHttpRequest();
    } else if (window.ActiveXObject) {
                req = new ActiveXObject('Microsoft.XMLHTTP');
        }
 
    if (req) {	 
        req.open("GET", url + '?r='+Math.random()+'&'+params, true);
        req.setRequestHeader("Content-Type", "application/x-www-form-urlencoded"); 
        req.setRequestHeader("Accept-Charset", "UTF-8"); 
        req.setRequestHeader("Accept-Language", "ru, en");
        req.onreadystatechange = processReqChange;
        req.send(null);
    }
}
 
function processReqChange()
{

    // "complete"
    if (req.readyState == 4) {
        // "OK"
        if (req.status == 200) {
		    var div=document.getElementById('cont');
            div.innerHTML=req.responseText;
			div.ongetdata(div.innerHTML);
        } else {
            alert("Сервер занят:\n" +
                req.statusText);
        }
    }
	
	req = null;
}

function land_func(id){
var params = 'id=' + encodeURIComponent(id);
loadXMLDoc('server_scripts/get_land.php', params);	
}