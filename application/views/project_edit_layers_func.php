<script>

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