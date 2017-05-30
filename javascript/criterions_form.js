function popupAddCriterion() {
    var popup = document.getElementById("addpopup");
    popup.classList.toggle("show");
}

function popupSavedCriterions() {
    var managecrt = document.getElementById("id_managecrt");
    managecrt.classList.remove("collapsed");
    var addpopup = document.getElementById("addpopup");
    addpopup.classList.remove("show");

    var popup = document.getElementById("crtbank-dialogue");
    popup.classList.toggle("show");
    var lightbox = document.getElementById("lightbox");
    lightbox.classList.toggle("show");
    var popup = document.getElementById("page-mod-groupevaluation-criterions");
    popup.classList.toggle("lockscroll");

    var i;
    var crtsavedanswers = document.getElementsByClassName("crt-saved-answers");
    for (i = 0; i < crtsavedanswers.length; i++) {
        crtsavedanswers[i].classList.remove("ansexpanded");
    }
}

function expandAnswers(crtid, strshow, strhide, saved) {
    var button = document.getElementById("expandbutton_" + crtid);
    var text = button.innerHTML;
    if (text == strshow) {
      document.getElementById("expandbutton_" + crtid).innerHTML = strhide;
    } else {
      document.getElementById("expandbutton_" + crtid).innerHTML = strshow;
    }

    if (saved) {
      var elementid = "crtsavedans_" + crtid;
    } else {
      var elementid = "crtanswers_" + crtid;
    }
    var criterionans = document.getElementById(elementid);
    criterionans.classList.toggle("ansexpanded");
}

function expandAllAnswers(strexpand, strcollapse, srcexpand, srccollapse, saved) {
    var i;
    var expandvalue = '<img src="'+srcexpand+'">' + strexpand;
    var collapsevalue = '<img src="'+srccollapse+'">' + strcollapse;

    if (saved) {
      var classname = "crt-saved-answers";
      var idbutton = "expandallsavedbutton";
    } else {
      var classname = "crt-edit-answers";
      var idbutton = "expandallbutton";
    }

    var button = document.getElementById("expandallbutton");
    var text = button.innerHTML;
    var crtsavedanswers = document.getElementsByClassName(classname);

    if (text == expandvalue) {
      document.getElementById("expandallbutton").innerHTML = collapsevalue;

      for (i = 0; i < crtsavedanswers.length; i++) {
          crtsavedanswers[i].classList.add("ansexpanded");
      }
    } else {
      document.getElementById("expandallbutton").innerHTML = expandvalue;

      for (i = 0; i < crtsavedanswers.length; i++) {
          crtsavedanswers[i].classList.remove("ansexpanded");
      }
    }
}

function notIncludeInGrade() {
  var checked = document.getElementById("includecheckbox").checked;
  var weight = document.getElementById("weight");
  var auxweight = document.getElementById("auxweight");
  var id_weight = document.getElementById("id_weight");

  if (checked) {
    auxweight.value = weight.value;
    weight.value = 0;
    weight.setAttribute("disabled", "disabled");
    id_weight.removeAttribute("disabled");
  } else {
    weight.value = auxweight.value;
    weight.removeAttribute("disabled");
    id_weight.setAttribute("disabled", "disabled");
  }
}

function addAnswer(stranswer, strvalue, strposition) {
    // Container <div> where dynamic content will be placed
    var answerscontainer = document.getElementById("answerscontainer");
    n = answerscontainer.childElementCount + 1;

    //Update value of hidden element "numanswers"
    document.getElementById("numanswers").value = n;

    for (i = 1; i < n; i++) {
      tagweight = document.getElementById("tagvalue_" + i).value;
      if ((typeof(tagweight) != "undefined") && (tagweight != null) && (tagweight != "null")) {
        tagweight = 0;
      }
      tagposition = document.getElementById("tagposition_" + i).value;
      if ((typeof(tagposition) != "undefined") && (tagposition != null) && (tagposition != "null")) {
        tagposition = 1;
      }
      texto = "<input name=\"tagcheckbox_"+i+"\" value=\"0\" id=\"tagcheckbox_"+i+"\" type=\"checkbox\" ";
      texto += "onchange='checkboxChanged(\"tagcheckbox_"+i+"\")'></input>";
      texto += stranswer + " " + i + "<br/>";
      texto += strvalue + ": <select name=\"tagvalue_" + i + "\" id=\"tagvalue_" + i + "\" class=\"answeight\">";
      for (j=0;j<=100;j++) {
        if (j == Math.round(tagweight)) {
          texto += "<option selected=\"selected\" value=\"" + j + "\">" + j + "%</option>";
        } else {
          texto += "<option value=\"" + j + "\">" + j + "%</option>";
        }
      }
      texto += "</select><br/>";

      texto += strposition + ": ";
      onchange = "onchange=\"changeSelectPositions();\"";
      texto += "<select name=\"tagposition_" + i + "\" id=\"tagposition_" + i + "\" class=\"answeight\" " + onchange + ">";
      for (j=1;j<n;j++) {
        if (j == tagposition) {
          texto += "<option selected=\"selected\" value=\"" + j + "\">" + j + "</option>";
        } else {
          texto += "<option value=\"" + j + "\">" + j + "</option>";
        }
      }
      texto += "</select>";

      document.getElementById("fitem_id_tag_" + i).firstChild.firstChild.innerHTML = texto;
    }

    var qoptcontainer = document.createElement("div");
    qoptcontainer.id = "qoptcontainer_" + n;
    qoptcontainer.setAttribute("class", "qoptcontainer");

      var fitem_fitem_ftextarea = document.createElement("div");
      fitem_fitem_ftextarea.id = "fitem_id_tag_" + n;
      fitem_fitem_ftextarea.setAttribute("class", "fitem fitem_ftextarea");

          var fitemtitle = document.createElement("div");
          fitemtitle.setAttribute("class", "fitemtitle");

              var label = document.createElement("label");
              label.setAttribute("for", "id_tag_" + n);

              texto = "<input name=\"tagcheckbox_"+n+"\" value=\"0\" id=\"tagcheckbox_"+n+"\" type=\"checkbox\" ";
              texto += "onchange='checkboxChanged(\"tagcheckbox_"+n+"\")'></input>";
              texto += stranswer + " " + n + "<br/>";
              texto += strvalue + ": <select name=\"tagvalue_" + n + "\" id=\"tagvalue_" + n + "\" class=\"answeight\">";
              for (j=0;j<=100;j++) {
                texto += "<option value=\"" + j + "\">" + j + "%</option>";
              }
              texto += "</select><br/>";

              texto += strposition + ": ";
              onchange = "onchange=\"changeSelectPositions();\"";
              texto += "<select name=\"tagposition_" + n + "\" id=\"tagposition_" + n + "\" class=\"answeight\" " + onchange + ">";
              for (j=1;j<n;j++) {
                texto += "<option value=\"" + j + "\">" + j + "</option>";
              }
              texto += "<option selected=\"selected\" value=\"" + n + "\">" + n + "</option>";
              texto += "</select>";

              label.innerHTML = texto;

          var ftextarea = document.createElement("div");
          ftextarea.setAttribute("class", "felement ftextarea");

              var textarea = document.createElement("textarea");
              textarea.id = "id_tag_" + n;
              textarea.name = "tag_" + n;
              textarea.setAttribute("class", "qopts");
              textarea.setAttribute("wrap", "virtual");
              textarea.setAttribute("required", "required");

    fitemtitle.appendChild(label);
    ftextarea.appendChild(textarea);
    fitem_fitem_ftextarea.appendChild(fitemtitle);
    fitem_fitem_ftextarea.appendChild(ftextarea);
    qoptcontainer.appendChild(fitem_fitem_ftextarea);

    answerscontainer.appendChild(qoptcontainer);
}

function removeAnswers(stranswer, strvalue, strposition) {
    // Container <div> where dynamic content will be placed
    var answerscontainer = document.getElementById("answerscontainer");
    n = answerscontainer.childElementCount;

    for (i = 1; i <= n; i++) {
      checked = document.getElementById("tagcheckbox_" + i).value;
      if (checked == 1) {
        var qoptcontainer = document.getElementById("qoptcontainer_" + i);
        // Remove answer
        answerscontainer.removeChild(qoptcontainer);
      }
    }
    // Update answers number
    n_old = n;
    n = answerscontainer.childElementCount;
    //Update value of hidden element "numanswers"
    document.getElementById("numanswers").value = n;

    i = 1;
    for (i_old = 1; i_old <= n_old; i_old++) {
      tagweight = document.getElementById("tagvalue_" + i_old);
      tagposition = document.getElementById("tagposition_" + i_old);

      if ((typeof(tagweight) != "undefined") && (tagweight != null) && (tagweight != "null") &&
          (typeof(tagposition) != "undefined") && (tagposition != null) && (tagposition != "null")) {
        texto = "<input name=\"tagcheckbox_"+i+"\" value=\"0\" id=\"tagcheckbox_"+i+"\" type=\"checkbox\" ";
        texto += "onchange='checkboxChanged(\"tagcheckbox_"+i+"\")'></input>";
        texto += stranswer + " " + i + "<br/>";
        texto += strvalue + ": <select name=\"tagvalue_" + i + "\" id=\"tagvalue_" + i + "\" class=\"answeight\">";
        for (j=0;j<=100;j++) {
          if (j == Math.round(tagweight.value)) {
            texto += "<option selected=\"selected\" value=\"" + j + "\">" + j + "%</option>";
          } else {
            texto += "<option value=\"" + j + "\">" + j + "%</option>";
          }
        }
        texto += "</select><br/>";

        texto += strposition + ": ";
        onchange = "onchange=\"changeSelectPositions();\"";
        texto += "<select name=\"tagposition_" + i + "\" id=\"tagposition_" + i + "\" class=\"answeight\" " + onchange + ">";
        for (j=1;j<=n;j++) {
          if (j == i) {
            texto += "<option selected=\"selected\" value=\"" + j + "\">" + j + "</option>";
          } else {
            texto += "<option value=\"" + j + "\">" + j + "</option>";
          }
        }
        texto += "</select>";

        document.getElementById("fitem_id_tag_" + i_old).firstChild.firstChild.innerHTML = texto;
        document.getElementById("fitem_id_tag_" + i_old).firstChild.firstChild.setAttribute("for", "id_tag_" + i);

        document.getElementById("fitem_id_tag_" + i_old).id = "fitem_id_tag_" + i;
        document.getElementById("qoptcontainer_" + i_old).id = "qoptcontainer_" + i;

        document.getElementById("id_tag_" + i_old).id = "id_tag_" + i;

        i++;
      }
    }
}

function checkHasAnswers(msg) {
  var answerscontainer = document.getElementById("answerscontainer");
  n = answerscontainer.childElementCount;

  if(n > 0) {
    return true;
  } else {
    alert(msg);
    return false;
  }
}

function checkboxChanged(id) {

  if((document.getElementById(id).value == 0)) {
    document.getElementById(id).value=1;
  } else {
    document.getElementById(id).value=0;
  }
}


//TODO borrar (sortable)
$(document).ready(function(){
            $(".list").sortable({
                connectWith: '.list',
                opacity: 0.5,
                tolerance: 'pointer',
                placeholder: 'place_holder_element',
                helper: function(event, el) {
                    var myclone = el.clone();
                    $('body').append(myclone);
                    return myclone;
                },
                receive: function( event, ui ) {
					var list=$(this).sortable().attr("id");
					$("#result_1").attr("value",ui.item.attr("id")+" movido a la lista "+list);
                },
                stop:	function( event, ui ) {
					$("#result_2").attr("value",ui.item.attr("id")+" movido a posición "+(ui.item.index()+1));
                }
            }).disableSelection();
        });

function savePosition(url) {

  if (window.XMLHttpRequest) {
    // code for IE7+, Firefox, Chrome, Opera, Safari
    xmlhttp=new XMLHttpRequest();
  } else { // code for IE6, IE5
    xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
  }
  var params = "id=ipsum&pos=binny";
  xmlhttp.onreadystatechange=function() {
    if (this.readyState==4 && this.status==200) {
      document.getElementById("txtHint").innerHTML=this.responseText;
    }
  }
  xmlhttp.open("POST", url, true);
  xmlhttp.send(params);
}

/*// TODO Borrar
 require(["jquery", "core/modal_factory"], function($, ModalFactory) {
   var trigger = $("#create-modal").click();
   ModalFactory.create({
     title: "test title",
     body: "<p>test body content</p>",
     footer: "test footer content",
   }, trigger)
   .done(function(modal) {
     // Do what you want with your new modal.
   });
 });*/
