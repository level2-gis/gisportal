//gisportal common JS stuff

function showError(msg) {
    bootbox.alert({
        title: "Error",
        message: msg+"!",
        size: 'small'
    });
}

function onUploadFormSubmit() {

    var form = $('#uploadForm')[0];
    var client = $('#client_id')[0].value;

    if(client=='') {
        showError(GP.clientRequired);
        return false;
    }

    var editingProject = $('#project_name')[0].value;

    if ($('#userfile')[0].files.length==0) {
        showError(GP.noFile);
        return false;
    }

    var file = $('#userfile')[0].files[0].name;
    var newProject = file.split('.')[0];
    var ext = file.split('.')[1];

    form.action = form.action+client;

    //client side validation
    if (ext.toLowerCase() !== 'qgs') {
        showError(GP.onlyQgs);
        form.reset();
        return false;
    }
    if (editingProject && editingProject !== newProject) {
        showError(GP.differentProjects+" "+editingProject+ ": "+newProject);
        form.reset();
        return false;
    }
}

function confirmLink(msg,name,url) {
    bootbox.confirm(msg.replace('{name}',name), function(doIt){
        if(doIt) {
            window.location = url;
        }
    });
}

function onClientChange(sel,action)
{
    var val = sel.value;
    var div = $('#templateDiv');

    if(action == 2) {
        div = $('#uploadDiv');
    }

    if (val > 0) {
        div.show();
    } else {
        div.hide();
    }
}

function checkValues() {
    var baseList = $('#lstBase2').getValues();
    var baseIds = document.getElementById('base_ids');
    baseIds.value = ('{'+baseList.join()+'}');

    var extraList = $('#lstExtra2').getValues();
    var extraIds = document.getElementById('extra_ids');
    extraIds.value = ('{'+extraList.join()+'}');
}

function moveItem(list1, list2) {
    $('select').moveToListAndDelete(list1, list2);
    //e.preventDefault();
}

function moveAllItems(list1, list2) {
    $('select').moveAllToListAndDelete(list1, list2);
    //e.preventDefault();
}

function moveUp(list) {
    $('select').moveUpDown(list, true, false);
}

function moveDown(list) {
    $('select').moveUpDown(list, false, true);
}

//    //example calling over id
//
//    $('#btnAllLeft').click(function (e) {
//        $('select').moveAllToListAndDelete('#lstBox2', '#lstBox1');
//        e.preventDefault();
//    });