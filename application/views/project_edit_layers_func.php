<script>

    function onUploadFormSubmit() {

        var form = $('#uploadForm')[0];
        var client = $('#client_id')[0].value;

        var editingProject = $('#project_name')[0].value;
        var file = $('#userfile')[0].files[0].name;

        var newProject = file.split('.')[0];
        var ext = file.split('.')[1];

        form.action = form.action+client;

        //client side validation
        if (ext.toLowerCase() !== 'qgs') {
            alert ("Only QGS project file allowed!");
            form.reset();
            return false;
        }
        if (editingProject && editingProject !== newProject) {
            alert("Different projects "+editingProject+ ": "+newProject);
            form.reset();
            return false;
        }
    }


    function onClientChange(sel)
    {
        var val = sel.value;
        var upload = $('#uploadDiv');
        if (val > 0) {
            upload.show();
        } else {
            upload.hide();
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
</script>