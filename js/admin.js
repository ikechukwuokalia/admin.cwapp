// Login and accout request
function LoginWithOtp (code) {
  $("form#admin-sign-in input[name=otp]").val(code.replace(/\s/g,'').toUpperCase());
  cwos.faderBox.close();
  setTimeout(() => {
    $("form#admin-sign-in").submit();
  }, 500);
}
const signIn = (resp) => {
  if( resp && ( resp.errors.length <= 0 || resp.status == "0.0") && !resp.otp_req ){
    $('#admin-sign-in').trigger("reset");
    if ( resp.rdt.length > 0 ) {
      setTimeout(function(){  redirectTo(resp.rdt); },3200);
    } else {
      setTimeout(function(){ removeAlert(); },3200);
    }
  } else {
    if (resp.otp_req == true && resp.email) {
      $("form#admin-sign-in input[name=user]").val(resp.email);
      setTimeout(function(){
        removeAlert();
        cwos.faderBox.url('/app/helper/otp/send-email', {
          email : resp.email,
          cb : "LoginWithOtp",
          MUST_EXIST : true,
          code_variant : "numbers",
          code_length : 8
        }, { method : "POST", exitBtn : false });
      }, 820);
    }
  }
}
const chkSignUp = (frm) => {
  let $frm = $(frm);
  if ($frm.find("input#signup-otp").length && $frm.find("input#signup-otp").val().length) {
    cwos.form.submit(frm, signUp);
  } else {
    // get otp
    cwos.faderBox.url('/app/helper/otp/send-email', {
      email : usreml,
      cb : "RegWithOtp",
      MUST_EXIST : true,
      code_variant : "numbers",
      code_length : 8
    }, { method : "POST", exitBtn : false });
    // console.error("OTP required");
  }
}
function RegWithOtp (code) {
  $("form#adm-signup-form input[name=otp]").val(code.replace(/\s/g,'').toUpperCase());
  cwos.faderBox.close();
  setTimeout(() => {
    $("form#adm-signup-form").submit();
  }, 500);
}
const signUp = (resp) => {
  if( resp && ( resp.errors.length <= 0 || resp.status == "0.0") ){
    $('#adm-signup-form').trigger("reset");
    if ( resp.rdt.length > 0 ) {
      setTimeout(function(){ redirectTo(resp.rdt); },3200);
    } else {
      setTimeout(function(){ removeAlert(); },3200);
    }
  } else {
    if ("otp" in resp && resp.otp == false) {
      $('#adm-signup-form input[name=otp]').val("");
    }
  }
}

function listUser (users) {
  let conf = pConf(0);
  let html = "";
  $.each(users, function(_i, usr) {
    html += "<tr>";
      html += `<td> <a href="${usr.avatar}" title="" data-fancybox="${usr.code}"><img class="data-avatar" src="${setGet(usr.avatar, {getsize:'80x80'})}" alt=""></a> </td>`;
      html += `<td>`;
        html += `<code class=bold" onclick="clipboardCopy('${usr.code}');">${usr.codeSplit}</code>`;
        html += ' | ';
        html += ' <span class="color-';
          if (usr.status == "ACTIVE") {
            html += "green";
          } else if (['BANNED', 'SUSPENDED', 'REJECTED', 'INACTIVE'].includes(usr.status)) {
            html += "red";
          } else if (['INVITED','PENDING','REQUESTING'].includes(usr.status)) {
            html += "amber";
          } else {
            html += "orange";
          }
        html += `">${usr.status}</span>`;
      html += `</td>`;
      html += `<td>${usr.workGroup}</td>`;
      html += `<td>`;
          html += `<a href="#" onclick="cwos.faderBox.url('/app/admin/view/user-profile',{code:'${usr.user}'})" title="${usr.name} ${usr.surname}">`;
            html += `${usr.name} (${usr.userSplit})`;
          html += `</a>`; 
      html += `</td>`;
      html += `<td> <a href="mailto:${usr.email}">${usr.emailMask}<a/></td>`;
      html += `<td> <a href="tel:${usr.phone}">${usr.phoneMask}<a/></td>`;
      html += `<td>`;
          let btns = [];
          if (usr.status == "ACTIVE") {
            btns.push(`<button onclick="patchUsr('${usr.code}', 'BANNED');" type="button" class="theme-button mini red no-shadow" title="ban"><i class="fas fa-ban"></i> Ban user</button>`);

            btns.push(`<button onclick="patchUsr('${usr.code}', 'SUSPEND');" type="button" class="theme-button mini amber no-shadow" title="Suspend"><i class="fas fa-traffic-light-stop"></i> Suspend</button>`);
          } if (usr.status == "REQUESTING") {
            btns.push(`<button onclick="cwos.faderBox.url('/index/accept-user', {code:'${usr.code}'}, {exitBtn: true});" type="button" class="theme-button mini green no-shadow" title="Accept user"><i class="fas fa-check-circle"></i> Accept</button>`);

            btns.push(`<button onclick="patchUsr('${usr.code}', 'REJECTED');" type="button" class="theme-button mini red no-shadow" title="Reject user"><i class="fas fa-times"></i> Reject</button>`);
          }
        html += btns.join(" ");
      html += `</td>`;
    html += "</tr>";
  });
  $(`${conf.container}`).html(html);
};
const patchOpt = (id, enabled) => {
  let frm = $("#patch-opt-form");
  if (frm.length && id && confirm(`Do you want to ${enabled == true ? 'enable' : 'disable'} this option`)) {
    frm.find("input[name=id]").val(id);
    frm.find("input[name=enabled]").val(enabled == true ? 1 : 0);
    frm.submit();
  }
}
function lsStOpt (options) {
  let conf = pConf(0);
  let html = "";
  $.each(options, function(_i, opt) {
    html += "<tr>";
      html += `<td>`;
        html +=  `<code onclick="clipboardCopy('${opt.name}');" class="bold color-`;
          if (opt.enabled == true) {
            html += "green";
          } else {
            html += "red";
          }
        html += `">${opt.name}</code>`;
      html += `</td>`;
      html += `<td>${opt.type_title}</td>`;
      html += `<td> ${opt.title}</td>`;
      html += `<td>`;
          let btns = [];
          if (opt.enabled == true) {
            btns.push(`<button onclick="cwos.faderBox.url('/app/admin/post/setting-option', {id:${opt.id}, callback: 'requery'}, {exitBtn:true});" type="button" class="theme-button mini blue no-shadow"><i class="fas fa-edit"></i> Edit</button>`);
            
            btns.push(`<button onclick="cwos.faderBox.url('/app/admin/post/domain-setting', {key:'${opt.name}'}, {exitBtn:true});" type="button" class="theme-button mini olive no-shadow"><i class="fas fa-cog"></i> Set</button>`);

            btns.push(`<button onclick="patchOpt(${opt.id}, false);" type="button" class="theme-button mini red no-shadow" title="Disable"><i class="fas fa-ban"></i> Disable</button>`);
          } else {
            btns.push(`<button onclick="patchOpt(${opt.id}, true);" type="button" class="theme-button mini green no-shadow" title="Enable"><i class="fas fa-check-circle"></i> Enable</button>`);
          }
        html += btns.join(" ");
      html += `</td>`;
    html += "</tr>";
  });
  $(`${conf.container}`).html(html);
};
const delWkDomain = (name) => {
  let frm = $("#delete-domain-form");
  if (name && frm.length && confirm(`Do you want to delete this domain?`)) {
    frm.find("input[name=name]").val(name);
    frm.submit();
  }
}
function lsWkDomain (domains) {
  let conf = pConf(0);
  let html = "";
  $.each(domains, function(_i, dmn) {
    html += "<tr>";
      html += `<td> <code onclick="clipboardCopy('${dmn.name}');" class="bold color-blue">${dmn.name}</code> </td>`;
      html += `<td>${dmn.title}</td>`;
      html += `<td>${dmn.description}</td>`;
      html += `<td> ${dmn.paths}</td>`;
      html += `<td>`;
      let btns = [];
      btns.push(`<button onclick="cwos.faderBox.url('/app/admin/post/work-domain', {name:'${dmn.name}', callback: 'requery'}, {exitBtn:true});" type="button" class="theme-button mini blue no-shadow"><i class="fas fa-edit"></i> Edit</button>`);
      
      btns.push(`<button onclick="cwos.faderBox.url('/app/admin/post/work-path', {domain:'${dmn.name}', callback: 'requery'}, {exitBtn:true});" type="button" class="theme-button mini amber no-shadow"><i class="fas fa-plus"></i> Work path</button>`);
      
      btns.push(`<button onclick="delWkDomain('${dmn.name}');" type="button" class="theme-button mini red no-shadow" title="Delete"><i class="fas fa-trash"></i> Delete</button>`);
      html += btns.join(" ");
      html += `</td>`;
    html += "</tr>";
  });
  $(`${conf.container}`).html(html);
};
const delWkPath = (id = 0) => {
  let frm = $("#delete-wkpth-form");
  if (id && frm.length && confirm(`Do you want to delete this path?`)) {
    frm.find("input[name=id]").val(id);
    frm.submit();
  }
}
function lsWkPath (paths) {
  let conf = pConf(0);
  let html = "";
  $.each(paths, function(_i, pth) {
    html += "<tr>";
      html += `<td> <code>${pth.domainPath}</code><code onclick="clipboardCopy('${pth.path}');" class="bold color-blue">${pth.path}</code></td>`;
      html += `<td> <code onclick="clipboardCopy('${pth.domain}');" class="bold color-amber"><a class="color-in" href="${pth.domainPath}">${pth.domain}</a></code></td>`;
      html += `<td> ${pth.title}</td>`;
      html += `<td> ${pth.navVisible == true ? "ON" : "OFF"}</td>`;
      html += `<td>`;
      let btns = [];
      btns.push(`<button onclick="cwos.faderBox.url('/app/admin/post/work-path', {id:${pth.id}, callback: 'requery'}, {exitBtn:true});" type="button" class="theme-button mini blue no-shadow"><i class="fas fa-edit"></i> Edit</button>`);
      
      
      btns.push(`<button onclick="delWkPath(${pth.id});" type="button" class="theme-button mini red no-shadow" title="Delete"><i class="fas fa-trash"></i> Delete</button>`);
      html += btns.join(" ");
      html += `</td>`;
    html += "</tr>";
  });
  $(`${conf.container}`).html(html);
};
const delPathAccess = (id = 0) => {
  let frm = $("#delete-patha-form");
  if (id && frm.length && confirm(`Do you want to delete this path-access?`)) {
    frm.find("input[name=id]").val(id);
    frm.submit();
  }
}
function lsPathAccess (paths) {
  let conf = pConf(0);
  let html = "";
  $.each(paths, function(_i, pta) {
    html += "<tr>";
      html += `<td title="${pta.userName}"> <code onclick="clipboardCopy('${pta.user}');" class="bold color-amber"> ${pta.userSplit}</code></td>`;
      html += `<td> <code onclick="clipboardCopy('${pta.domain}');" class="bold"><a href="${pta.domainPath}">${pta.domain}</a></code></td>`;
      html += `<td> <code class="bold">${pta.type}</code></td>`;
      html += `<td>`; 
      if (pta.type == "PATH") {
        html += `<code>${pta.domainPath}</code>`;
      } 
      html += `<code onclick="clipboardCopy('${pta.path}');" class="bold color-blue">${pta.path}</code>`;
      html += `</td>`;
      html += `<td> <code>${pta.accessScope.join(", ")}</code></td>`;
      html += `<td>`;
      let btns = [];
      btns.push(`<button onclick="cwos.faderBox.url('/app/admin/post/${pta.type.toLowerCase()}-access', {path:'${pta.path}', domain:'${pta.domain}', user: '${pta.user}', callback: 'requery'}, {exitBtn:true});" type="button" class="theme-button mini blue no-shadow"><i class="fas fa-edit"></i> Edit</button>`);
    
      btns.push(`<button onclick="delPathAccess(${pta.id});" type="button" class="theme-button mini red no-shadow" title="Delete"><i class="fas fa-trash"></i> Delete</button>`);
      html += btns.join(" ");
      html += `</td>`;
    html += "</tr>";
  });
  $(`${conf.container}`).html(html);
};
const delAtp = (name) => {
  let frm = $("#delete-accstp-form");
  if (name && frm.length && confirm(`Do you want to delete this item?`)) {
    frm.find("input[name=name]").val(name);
    frm.submit();
  }
}
function lsAccessType (types) {
  let conf = pConf(0);
  let html = "";
  $.each(types, function(_i, atp) {
    html += "<tr>";
      html += `<td> <code onclick="clipboardCopy('${atp.name}');" class="bold color-blue">${atp.name}</code> </td>`;
      html += `<td> ${atp.rank}</td>`;
      html += `<td title="${atp.description}">${atp.title}</td>`;
      html += `<td> <code class="color-amber">${atp.scope.join(", ")}</code></td>`;
      html += `<td>`;
        let btns = [];
        btns.push(`<button onclick="cwos.faderBox.url('/app/admin/post/access-type', {name:'${atp.name}', callback: 'requery'}, {exitBtn:true});" type="button" class="theme-button mini blue no-shadow"><i class="fas fa-edit"></i> Edit</button>`);
              
        btns.push(`<button onclick="delAtp('${atp.name}');" type="button" class="theme-button mini red no-shadow" title="Delete"><i class="fas fa-trash"></i> Delete</button>`);
        html += btns.join(" ");
      html += `</td>`;
    html += "</tr>";
  });
  $(`${conf.container}`).html(html);
};
const delAcsco = (name) => {
  let frm = $("#del4rm-asco-form");
  if (name && frm.length && confirm(`Do you want to delete this access scope?`)) {
    frm.find("input[name=name]").val(name);
    frm.submit();
  }
}
function lsAcsco (scopes) {
  let conf = pConf(1);
  let html = "";
  $.each(scopes, function(_i, asco) {
    html += "<tr>";
      html += `<td> <code onclick="clipboardCopy('${asco.name}');" class="bold color-blue">${asco.name}</code> </td>`;
      html += `<td>${asco.rank}</td>`;
      html += `<td>${asco.description}</td>`;
      html += `<td>`;
        let btns = [];
        btns.push(`<button onclick="cwos.faderBox.url('/app/admin/post/access-scope', {name:'${asco.name}', cb: 'requery1'}, {exitBtn:true});" type="button" class="theme-button mini blue no-shadow"><i class="fas fa-edit"></i> Edit</button>`);
              
        btns.push(`<button onclick="delAcsco('${asco.name}');" type="button" class="theme-button mini red no-shadow" title="Delete"><i class="fas fa-trash"></i> Delete</button>`);
        html += btns.join(" ");
      html += `</td>`;
    html += "</tr>";
  });
  $(`${conf.container}`).html(html);
};
const delRcT = (name) => {
  let frm = $("#delete-rctg-form");
  if (name && frm.length && confirm(`Do you want to delete this resource type?`)) {
    frm.find("input[name=name]").val(name);
    frm.submit();
  }
}
function lsRcT (types) {
  let conf = pConf();
  let html = "";
  $.each(types, function(_i, tg) {
    html += "<tr>";
      html += `<td> <code onclick="clipboardCopy('${tg.name}');" class="bold color-blue">${tg.name}</code> </td>`;
      html += `<td>${tg.restricted == true ? "On" : "Off"}</td>`;
      html += `<td>${tg.title}</td>`;
      html += `<td>${tg.description}</td>`;
      html += `<td>`;
        let btns = [];
        btns.push(`<button onclick="cwos.faderBox.url('/app/admin/post/resource-type', {name:'${tg.name}', callback: 'requery'}, {exitBtn:true});" type="button" class="theme-button mini blue no-shadow"><i class="fas fa-edit"></i> Edit</button>`);

        btns.push(`<button onclick="cwos.faderBox.url('/app/admin/list/resource-access', {resource:'${tg.name}'}, {exitBtn:true});" type="button" class="theme-button mini amber no-shadow"><i class="fas fa-cog"></i> Access</button>`);
              
        btns.push(`<button onclick="delRcT('${tg.name}');" type="button" class="theme-button mini red no-shadow" title="Delete"><i class="fas fa-trash"></i> Delete</button>`);
        html += btns.join(" ");
      html += `</td>`;
    html += "</tr>";
  });
  $(`${conf.container}`).html(html);
};
const delKwd = (id) => {
  let frm = $("#delete-rkwrd-form");
  if (id && frm.length && confirm(`Do you want to delete this keyword?`)) {
    frm.find("input[name=id]").val(id);
    frm.submit();
  }
}
function lsKwd (keywords) {
  let conf = pConf();
  let html = "";
  $.each(keywords, function(_i, kwd) {
    html += "<tr>";
      html += `<td> <code onclick="clipboardCopy('${kwd.keyword}');" class="bold color-${kwd.type == 'RESTRICTED' ? 'red' : 'blue'}">${kwd.keyword}</code> </td>`;
      html += `<td>${kwd.type}</td>`;
      html += `<td>`;
        let btns = [];
        btns.push(`<button onclick="cwos.faderBox.url('/app/admin/post/reserved-keyword', {id:${kwd.id}, callback: 'requery'}, {exitBtn:true});" type="button" class="theme-button mini blue no-shadow"><i class="fas fa-edit"></i> Edit</button>`);
              
        btns.push(`<button onclick="delKwd(${kwd.id});" type="button" class="theme-button mini red no-shadow" title="Delete"><i class="fas fa-trash"></i> Delete</button>`);
        html += btns.join(" ");
      html += `</td>`;
    html += "</tr>";
  });
  $(`${conf.container}`).html(html);
};
const delDmnSetting = (id, domain) => {
  let frm = $("#delete-setting-form");
  if (id && domain && frm.length && confirm(`Do you want to delete this setting?`)) {
    frm.find("input[name=id]").val(id);
    frm.find("input[name=domain]").val(domain);
    frm.submit();
  }
}
function lsSetting (settings) {
  let conf = pConf(0);
  let html = "";
  $.each(settings, function(_i, stt) {
    html += "<tr>";
      html += `<td> <code onclick="clipboardCopy('${stt.key}');" class="bold color-blue">${stt.key}</code> </td>`;
      html += `<td><code>${stt.user}</code></td>`;
      html += `<td> ${stt.title}</td>`;
      html += `<td> ${stt.value}</td>`;
      html += `<td>`;
          let btns = [];
          btns.push(`<button onclick="cwos.faderBox.url('/app/admin/post/domain-setting', {id: ${stt.id}, domain: '${stt.domain}', callback: 'requery'}, {exitBtn:true});" type="button" class="theme-button mini blue no-shadow"><i class="fas fa-edit"></i> Edit</button>`);

          btns.push(`<button onclick="delDmnSetting(${stt.id}, '${stt.domain}');" type="button" class="theme-button mini red no-shadow" title="Delete"><i class="fas fa-trash"></i> Delete</button>`);
        html += btns.join(" ");
      html += `</td>`;
    html += "</tr>";
  });
  $(`${conf.container}`).html(html);
};
const delResAccess = (id) => {
  let frm = $("#del4rm-reacs-form");
  if (id && frm.length && confirm(`Do you want to delete this access?`)) {
    frm.find("input[name=id]").val(id);
    frm.submit();
  }
}
function lsResAccess (access) {
  let conf = pConf(1);
  let html = "";
  $.each(access, function(_i, acs) {
    html += "<tr>";
      html += `<td> <code onclick="clipboardCopy('${acs.groupName}');" class="bold color-blue">${acs.groupName}</code> </td>`;
      html += `<td><code>${acs.scope.join(", ")}</code></td>`;
      html += `<td>`;
          let btns = [];
          btns.push(`<button onclick="cwos.faderBox.url('/app/admin/post/resource-access', {id: ${acs.id}, resource: '${acs.resource}', cb: 'requery1'}, {exitBtn:true});" type="button" class="theme-button mini blue no-shadow"><i class="fas fa-edit"></i> Edit</button>`);

          btns.push(`<button onclick="delResAccess(${acs.id});" type="button" class="theme-button mini red no-shadow" title="Delete"><i class="fas fa-trash"></i> Delete</button>`);
        html += btns.join(" ");
      html += `</td>`;
    html += "</tr>";
  });
  $(`${conf.container}`).html(html);
};
const gvAccess = (dmn, usr, type) => {
  if (dmn && usr && type) {
    cwos.faderBox.url(`/app/admin/post/${type.toLowerCase()}-access`, {
      domain: dmn,
      user: usr
    }, {exitBtn: true, type:"GET"});
  }
}
const postPtAcs = (frm, callback) => {
  frm = $(frm);
  let paths = {}
  if (frm.length) {
    let grp = frm.find(".scope-group");
    if (grp.length) {
      grp.each(function(_i, el) {
        $(el).find("input[type=checkbox]:checked").each(function(){
          let name = $(this).attr("name");
          if (typeof paths[$(this).data("path")] !== "object") paths[$(this).data("path")] = [];
          paths[$(this).data("path")].push($(this).val());
        });
      });
    }
    if (objectLength(paths)) {
      let rqScope = {}
      $.each(paths, function(name, scopes){
        rqScope[name] = scopes.join(",");
      });
      // send the request
      alert("Please wait .. .", {type:"progress", exit:false});
      $.ajax({
        url :  frm.attr("action"),
        dataType : "json",
        type : "POST",
        data: JSON.stringify({
          user: frm.find("input[name=user]").val(),
          domain: frm.find("input[name=domain]").val(),
          form: frm.find("input[name=form]").val(),
          CSRF_token: frm.find("input[name=CSRF_token]").val(),
          access: rqScope
        }),
        contentType: "application/json; charset=utf-8",
        traditional: true,
        success : function (resp) {
          if( resp && (resp.status == '0.0' || resp.errors.length <= 0) ){
            cwos.faderBox.close();
            alert(`<h2>[${resp.status}]: Success</h2> <p>${resp.message}</p>`);
            setTimeout(function(){
              removeAlert();
              callback(resp);
            },180);
          } else {
            alert(`<h2>[${resp.status}]: ${resp.message}</h2> <p>${resp.errors.join('<br>')}</p>`);
          }
        },
        error : function(xhr){
          alert(`<h2>[${xhr.status}]: Error ocured</h2> <p>${xhr.responseText}</p>`);
        }
      });  
      // cwos.faderBox.url(frm.attr("action"), rqScope, {exitBtn: true, dataType:"json", method:"post"}, callback);
    }
  }
}
// const page = (typeof cwos !== undefined && typeof cwos.config !== undefined && typeof cwos.config.page !== undefined) ? cwos.config.page : {}
// page .sub = [];
(function(){
  
})();