/**
 * Bootstrap Table Slovenian translation
 * Author: Uros
 */
(function ($) {
    'use strict';

    $.fn.bootstrapTable.locales['sl-SI'] = {
        formatLoadingMessage: function () {
            return 'Prosimo počakajte...';
        },
        formatRecordsPerPage: function (pageNumber) {
            return pageNumber + ' vrstic na stran';
        },
        formatShowingRows: function (pageFrom, pageTo, totalRows) {
            return 'Prikazujem ' + pageFrom + ' do ' + pageTo + ' od ' + totalRows + ' vrstic';
        },
        formatSearch: function () {
            return 'Iskanje';
        },
        formatNoMatches: function () {
            return 'Ni rezultatov';
        },
        formatPaginationSwitch: function () {
            return 'Skrij/Prikaži strani';
        },
        formatRefresh: function () {
            return 'Osveži';
        },
        formatToggle: function () {
            return 'Preklopi';
        },
        formatColumns: function () {
            return 'Stolpci';
        },
        formatAllRows: function () {
            return 'Vse';
        },
        formatExport: function () {
            return 'Izvozi podatke';
        },
        formatClearFilters: function () {
            return 'Pobriši filtre';
        }
    };

    $.extend($.fn.bootstrapTable.defaults, $.fn.bootstrapTable.locales['sl-SI']);

})(jQuery);
