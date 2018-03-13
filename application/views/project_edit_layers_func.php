<script>
/*    $('#btnAvenger').click(function (e) {
        $('select').moveToList('#StaffList', '#PresenterList');
        e.preventDefault();
    });

    $('#btnRemoveAvenger').click(function (e) {
        $('select').removeSelected('#PresenterList');
        e.preventDefault();
    });

    $('#btnAvengerUp').click(function (e) {
        $('select').moveUpDown('#PresenterList', true, false);
        e.preventDefault();
    });

    $('#btnAvengerDown').click(function (e) {
        $('select').moveUpDown('#PresenterList', false, true);
        e.preventDefault();
    });

    $('#btnShield').click(function (e) {
        $('select').moveToList('#StaffList', '#ContactList');
        e.preventDefault();
    });

    $('#btnRemoveShield').click(function (e) {
        $('select').removeSelected('#ContactList');
        e.preventDefault();
    });

     $('#btnJusticeLeague').click(function (e) {
     $('select').moveToList('#StaffList', '#FacilitatorList');
     e.preventDefault();
     });

     $('#btnRemoveJusticeLeague').click(function (e) {
     $('select').removeSelected('#FacilitatorList');
     e.preventDefault();
     });

     $('#btnJusticeLeagueUp').click(function (e) {
     $('select').moveUpDown('#FacilitatorList', true, false);
     e.preventDefault();
     });

     $('#btnJusticeLeagueDown').click(function (e) {
     $('select').moveUpDown('#FacilitatorList', false, true);
     e.preventDefault();
     });




    */

    function checkValues() {
        var baseList = $('#lstBox2').getValues();

        var baseIds = document.getElementById('base_ids');
        baseIds.value = ('{'+baseList.join()+'}');

        //TODO extra_ids
    }



    $('#btnShieldUp').click(function (e) {
        $('select').moveUpDown('#lstBox2', true, false);
        e.preventDefault();
    });

    $('#btnShieldDown').click(function (e) {
        $('select').moveUpDown('#lstBox2', false, true);
        e.preventDefault();
    });

    $('#btnRight').click(function (e) {
        $('select').moveToListAndDelete('#lstBox1', '#lstBox2');
        e.preventDefault();
    });

    $('#btnAllRight').click(function (e) {
        $('select').moveAllToListAndDelete('#lstBox1', '#lstBox2');
        e.preventDefault();
    });

    $('#btnLeft').click(function (e) {
        $('select').moveToListAndDelete('#lstBox2', '#lstBox1');
        e.preventDefault();
    });

    $('#btnAllLeft').click(function (e) {
        $('select').moveAllToListAndDelete('#lstBox2', '#lstBox1');
        e.preventDefault();
    });
</script>