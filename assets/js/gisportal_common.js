//gisportal common JS stuff
//TODO create client language files and extract text strings below

function showError(msg) {
    bootbox.alert({
        title: "Error",
        message: msg,
        size: 'small'
    });
}

function onUploadFormSubmit() {

    var form = $('#uploadForm')[0];
    var client = $('#client_id')[0].value;

    if(client=='') {
        showError("Client required!");
        return false;
    }

    var editingProject = $('#project_name')[0].value;

    if ($('#userfile')[0].files.length==0) {
        showError('No file!');
        return false;
    }

    var file = $('#userfile')[0].files[0].name;
    var newProject = file.split('.')[0];
    var ext = file.split('.')[1];

    form.action = form.action+client;

    //client side validation
    if (ext.toLowerCase() !== 'qgs') {
        showError("Only QGS project file allowed!");
        form.reset();
        return false;
    }
    if (editingProject && editingProject !== newProject) {
        showError("Different projects "+editingProject+ ": "+newProject);
        form.reset();
        return false;
    }
}

function confirmLink(url) {
    bootbox.confirm("Are you sure you want to remove project from database?</br></br>Note: No files will be deleted from server!", function(doIt){
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