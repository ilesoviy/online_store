
window.$ = function(id) {
	return document.getElementById(id);
};


var mainhtm = '<div  style="background-color:'+LR_minicolor+';padding:35px 15px 18px 15px;width:390px;border-radius:15px;"><div  style="background-color:#ffffff;padding:10px;" align="center"><div id="tab" style="width:350px;height:390px;background-color:#fff;">  <div class="tabList">	<ul>		<li class="cur1" id="li0" style="width:'+(lng=='en'?'150':'100')+'px;" onclick="show(0);hidli(1);">'+c6[23]+'</li>		'+(c75?'<li id="li1" onclick="showtel1()">'+c6[24]+'</li>':'')+'		<li class="kongge" style=" width:'+((c75?148:249)-(lng=='en'?50:0))+'px;"><input type="button" onmouseout="filter1(this);" onmouseover="filter0(this);" value="'+c6[0]+'" style="background: #5ba4ed none repeat scroll 0 0;padding:2px 6px;line-height: 18px;font-size:12px;border-radius: 4px;display:'+((c85==null)?'none':'')+'" class="btn1" onclick=""></li>	</ul>  </div>  <div class="tabCon">	<div id="d0" class="cur1"><table width="100%" border="0" cellspacing="3" cellpadding="0" style="margin-top: 0px;">                            <tbody>               <tr>                <td colspan="2" height="32"><p id="LY_pmt" class="p1" align="left" >'+c6[1]+'</p></td>              </tr>              <tr>                <td width="100" height="36" align="right">                <label class="red">*</label>                <label style="font-size:14px;">'+c6[2]+'</label></td>                <td align="left"><input type="text" id="ly_name" class="input1" style="color:#999;border: 1px solid #D5D5D5;" value="'+c6[3]+'" defaultval="'+c6[3]+'" onblur="inputblur(this)" onfocus="inputfocus(this)"></td>              </tr>              <tr>                <td width="100" height="36" align="right">                <label class="red">*</label>                <label>'+c6[4]+'</label></td>                <td align="left"><input type="text" id="ly_phone" class="input1" style="color:#999;border: 1px solid #D5D5D5;" value="'+c6[5]+'" defaultval="'+c6[5]+'" onblur="inputblur(this)" onfocus="inputfocus(this)"></td>              </tr>                <tr>                <td width="100" height="36" align="right">                                <label>'+c6[6]+'</label></td>                <td align="left"><input type="text" id="ly_email" class="input1" style="color:#999;border: 1px solid #D5D5D5;" value="'+c6[7]+'" defaultval="'+c6[7]+'" onblur="inputblur(this)" onfocus="inputfocus(this)"></td>              </tr>'+c12+'<tr>                <td width="100" align="right" style="vertical-align:top;padding-top:13px;height:118px;" id="ly_note_td">                <label class="red">*</label>                <label>'+c6[9]+'</label></td>                <td align="left"><textarea id="ly_note" class="input1" style="border: 1px solid #D5D5D5;padding: 8px 0px 8px 8px; color: rgb(153, 153, 153); resize: none; height: 100px;" defaultval="'+c6[10]+'" onblur="inputblur(this)" onfocus="inputfocus(this)">'+c6[10]+'</textarea></td>              </tr>              <tr id="yzmtr" style="display:none;"><td width="100" height="36" align="right"><label class="red">*</label><label>'+c6[11]+'</label></td><td align="left"><INPUT type="text" style="border: 1px solid #D5D5D5;width:66px;" size="6" id="ccode" class="input1"  onfocus="inputfocus(this)" onblur="inputblur(this);">&nbsp;<img  title="'+c6[19]+'" id="yzmimg" align="AbsBottom" style="cursor:pointer"  onclick="updateIMg();"></td></tr>                          <tr>                <td colspan="2" align="center" valign="top" height="30"><input id="ly_btn" type="button" onmouseout="filter1(this);" onmouseover="filter0(this);" style="background: #5ba4ed none repeat scroll 0 0;margin-top:2px;" class="btn1" value="'+c6[14]+'" onclick="LY_check();"></td>              </tr>            </tbody></table></div>	<div id="d1"><table width="100%" border="0" cellspacing="5" cellpadding="0" style="margin-top: 0px;">                            <tbody>               <tr>                <td align="center" colspan="2" height="66"><p style="color: #666;font-size: 16px;line-height: 30px;padding-top: 5px;" id="tel_P">'+c6[13]+'</p></td>              </tr> <tr id="yzmtr1" style="display:none;"><td width="130" height="36" align="right"><label class="red">*</label><label>'+c6[11]+'</label></td><td align="left" width="238"><INPUT type="text" style="border: 1px solid #D5D5D5;width:66px;" size="6" id="ccode1" class="input1" onchange=""  onblur="inputblur(this);" onfocus="inputfocus(this)">&nbsp;<img title="'+c6[19]+'" id="yzmimg1" align="AbsBottom" style="cursor:pointer"  onclick="updateIMg1();"></td></tr><tr>                <td id="teltd" align="center" height="90" colspan="2"><div class="telephone" style="display:block;padding:0;width:300px;height:48px;border-radius: 25px;background-color: ' + v2 + ";border: 1px solid " + v2 + ';"><input type="text" id="tel1" style="padding-left: 30px;line-height: 48px;height:48px;width:180px;border-radius: 25px 0 0 25px;font-size:18px;" class="bd" value="' + t3 + '" defaultval="'+t3+'" onfocus="inputfocus(this,1)" onblur="inputblur(this,1);LY_pmt_F1(0);"><input type="button" id="telbtn" class="btn" style="height:48px;width:90px;border-radius: 0 25px 25px 0;font-size:16px;background: ' + v2 + ' none repeat scroll 0 0;" value="' + c6[24] + '" onclick="LY_check1($(\'tel1\'))" onmouseover="filter0(this);"  onmouseout="filter1(this);"></div></td>              </tr><tr>                <td valign="top"  colspan="2">'+t2+'</td>              </tr>              </tbody></table></div>  </div></div></div></div>';



function init()
{
    if(c11==0)showtel1();
}

function Qpm()
{
    return '&id='+escape(LR_websiteid)+'&sid='+escape(LR_sid)+'&cid='+escape(LR_cid)+'&lng='+escape(lng)+'&p='+escape(c50)+'&e='+escape(LR_ex)+'&un='+escape(LR_un)+'&ud='+escape(LR_ud)+'&on='+escape(c53);
}
function Trim(a)
{
    return a.replace(/(^\s*)|(\s*$)/g, '');
}
function isTel(a)
{
    if(lng!='cn' && lng!='big5')return true;
  var isPhone = /^0([0-9]{2,3}-|[1-9]{2,3})?[0-9]{7,8}$/;
    var isMob=/^((\+?86)|(\(\+86\)))?(1[3456789][0123456789][0-9]{8})$/;
 var  isHKPhone = /^([\\+]852\s*[69]|(852)\s*[69]|[69])\d{3}\s*\d{4}$/;
 var  isTWPhone = /^(09)\d{8}$/;
 var isTWTel = /^0([0-9]{1,2})([\\-]|\s*)\d{6,8}$/;
 
 var  isTGPhone = /^(0[89])([\\-]|\s*)\d{4}([\\-]|\s*)\d{4}$/;
    var isTGTel = /^(0[23])([\\-]|\s*)\d{3}([\\-]|\s*)\d{4}$/;

    var isYNPhone = /^0(9([\\-]|\s*)|1([\\-]|\s*)[0-9]{1})\d{8}$/;
    var isYNTel = /^(04)([\\-]|\s*)\d{3}([\\-]|\s*)\d{4}$/;
 
    return (isMob.test(a) || isPhone.test(a)||isHKPhone.test(a) ||isTWPhone.test(a) ||isTWTel.test(a)||isTGPhone.test(a)||isTGTel.test(a)||isYNPhone.test(a)||isYNTel.test(a));

}
function LY_check1(obj)
{
    var tel=Trim(obj.value);if(tel==t3){LY_pmt_F1(t3);obj.focus();return;}
     if(!isTel(tel))
     {
         LY_pmt_F1(c6[21]);obj.focus();
        return;
     }
    var bb='tel='+escape(tel);
    if($('ccode1'))bb+='&ccode='+escape(Trim($('ccode1').value));
   bb+=Qpm();
    LY_pmt_F1(c6[20],1);
   LR_hcloopJS(LR_sysurl+'lr/sendnote160711.aspx',bb);
}
function LY_check()
{
    var aa='ly_name|ly_phone|ly_email|ly_note|ccode|skid'.split('|');
    var bb='';
   for (i = 0; i < aa.length; i++) 
   {
        var obj=$(aa[i]);if(!obj)continue;obj.value=Trim(obj.value);var b=getAttributeValue(obj,'defaultval');
         if(aa[i]=='ly_email')
        {
            if(obj.value!='' && obj.value!=b && !is_email(obj.value))
            {
                LY_pmt_F(c6[8]);
                obj.focus();
                return;
            }
        }
        else if(obj.value==b || obj.value=='')
        {
            if(aa[i]=='ccode')
            {
                if($('yzmtr').style.display!='')
                    continue;
                 b=c6[12];
            }
            LY_pmt_F(b);
            obj.focus();
            return;
        }
        else  if(aa[i]=='ly_phone')
        {
            if(obj.value!='' && obj.value!=b && !isTel(obj.value))
            {
                LY_pmt_F(c6[21]);
                obj.focus();
                return;
            }
        }
        bb+=aa[i]+'='+(obj.value==b?'':encodeURIComponent(obj.value))+'&';
    }
    bb+=Qpm();
    LY_pmt_F(c6[16]);
   LR_hcloopJS(LR_sysurl+'lr/sendnote160711.aspx',bb);
}
function updateIMg(a){if(a){ LY_pmt_F(c6[a]);}$('ly_note_td').style.height='82px';$('ly_note').style.height='64px';$('yzmtr').style.display='';$('yzmimg').src='CheckCode3.aspx?id=' + LR_siteid + '&sid=' + LR_sid + '&d=' + new Date().getTime();$('ccode').focus();}
function updateIMg1(a){if(a){LY_pmt_F1(c6[a]);}$('teltd').style.height='50px'; $('yzmtr1').style.display='';$('yzmimg1').src='CheckCode3.aspx?id=' + LR_siteid + '&sid=' + LR_sid + '&d=' + new Date().getTime();$('ccode1').focus();}
  function inputfocus(obj,a)
        {
            if(obj.value==getAttributeValue(obj,'defaultval'))obj.value = "";
            obj.style.color='black';
            obj.style.fontSize='14px';
            if(!a)obj.style.border = "2px solid #82C6ED";
        }
         function inputblur(obj,a)
        {
            if(obj.value=='')
            {
                obj.value = getAttributeValue(obj,'defaultval');
                if(lng=='en')obj.style.fontSize='12px';
                obj.style.color='rgb(153, 153, 153)';
            }
             if(!a){obj.style.border = "1px solid #D5D5D5";}
             if($('LY_pmt') && $('li0').className=="cur1")$('LY_pmt').innerHTML=c6[1];
           if($('tel_P') && $('li1') && $('li1').className=="cur1")$('tel_P').innerHTML=c6[13];
        }
function LY_pmt_F(a)
{
    $('LY_pmt').innerHTML=(a==0?c6[1]:'<label class="red">'+a+'</label>');
}
function showtel1(a)
{
    show(1);hidli(0);
    if(a)$('tel1').focus();
}
function LY_pmt_F1(a)
{
    $('tel_P').innerHTML=(a==0?c6[13]:'<label class="red">'+a+'</label>');
}
function LY_end(a,b)
{
    if(a==1){b?LY_pmt_F1(c6[22]):LY_pmt_F(c6[18]);return;}
    if(a==0){b?LY_pmt_F1(n3):LY_pmt_F(c6[17]);return;}
}
function is_email(object_name)
           {
           var string;
           string=new String(object_name);
           var len=string.length;
           if (string.indexOf('@',1)==-1||string.indexOf('.',1)==-1||string.length<7)
           {
	           return false;
           }
           if (string.charAt(1)=='.'||string.charAt(1)=='@')
           {
               return false;
           }
           if (string.charAt(len-1)=='.'||string.charAt(len-1)=='@')
           {
               return false;
           }
           return true;
           }
function show(a)
        {
            if($('li'+a))$('li'+a).className = "cur1";
            $('d'+a).className = "cur1";
        }
        function hidli(a)
        {
            if($('li'+a))$('li'+a).className = "";
            $('d'+a).className = "";
        }
        function getAttributeValue(o, a) {
		if (!o.attributes) {
			return null
		}
		var b = o.attributes;
		for (var i = 0; i < b.length; i++) {
			if (a.toLowerCase() == b[i].name.toLowerCase()) {
				return b[i].value
			}
		}
		return null;
	}
	function filter0(sobj) {
	sobj.style.backgroundColor = "#45c01a";
}

function filter1(sobj) {
	sobj.style.backgroundColor = v2;
}

