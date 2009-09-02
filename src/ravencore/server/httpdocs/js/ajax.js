
function showtr(elem,str)
{

  if(document.getElementById(elem).style.display == 'none')
    {
      document.getElementById(elem).style.display='';
      show_data(elem,str);

    }
  else
    {
      document.getElementById(elem).style.display='none';
    }

}

var ajaxh;
var area;

// area to show data, the type of data to show, and the data this type needs to know

function show_data(ar,str)
{ 
  
  area = ar;

  var url="ajax.php?" + str;

  ajaxh=which_ajax_obj(finished_state);
  ajaxh.open("GET", url , true);
  ajaxh.send(null);

}

function finished_state()
{
  if (ajaxh.readyState == 4 || ajaxh.readyState == "complete")
    document.getElementById(area).innerHTML=ajaxh.responseText;
}

function which_ajax_obj(handler)
{ 
  var ajaxObj = null;

  if (navigator.userAgent.indexOf("Opera")>=0)
    {
      alert("This doesn't work in Opera");
      return;
    }
  
  if (navigator.userAgent.indexOf("MSIE")>=0)
    {
      var strName="Msxml2.XMLHTTP";

      if (navigator.appVersion.indexOf("MSIE 5.5")>=0)
	strName="Microsoft.XMLHTTP";
      
      try
	{ 
	  ajaxObj = new ActiveXObject(strName);
	  ajaxObj.onreadystatechange = handler;
	  return ajaxObj;
	}

      catch(e)
	{
	  alert("AJAX ActiveX plugin appears to be disabled");
	  return;
	}
    }

  if (navigator.userAgent.indexOf("Mozilla")>=0)
    {
      ajaxObj = new XMLHttpRequest();
      ajaxObj.onload=handler;
      ajaxObj.onerror=handler;
      return ajaxObj;
    }

}
