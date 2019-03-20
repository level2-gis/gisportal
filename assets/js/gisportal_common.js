//gisportal common JS stuff

String.prototype.replaceAll = function(search, replacement) {
    var target = this;
    return target.replace(new RegExp(search, 'g'), replacement);
};

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
    bootbox.confirm(msg.replaceAll('{name}',name), function(doIt){
        if(doIt) {
            window.location = url;
        }
    });
}

function onProjectGroupEditClick() {
    var group = $('#project_group_id').val();
    if(group) {
        var url = window.location.origin + '/project_groups/edit/' + group;
        window.location = url;
    }
}

function onGroupChange(id,sel) {
    var btn = $('#projectGroupEditBtn');
    var val = sel.value;
    if(id==val) {
        btn.removeClass('disabled');
    } else {
        btn.addClass('disabled');
    }
}

function onClientChange(sel,action)
{
    var val = sel.value;
    var div = $('#templateDiv');
    var group = $('#project_group_id');
    var groupDiv = $('#groupDiv');
    var url = window.location.origin+'/project_groups/get_list/'+val;

    if(action == 2) {
        div = $('#uploadDiv');
    }

    if (val > 0) {
        div.show();

        //get new client groups
        group.empty();
        group.append('<option selected="true" disabled>'+GP.selectGroup+'</option>');
        group.prop('selectedIndex', 0);

        $.getJSON(url, function (data) {
            $.each(data, function (key, entry) {
                group.append($('<option></option>').attr('value', entry.id).text(entry.name));
            })
        });

        groupDiv.show();

    } else {
        div.hide();
        groupDiv.hide();
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