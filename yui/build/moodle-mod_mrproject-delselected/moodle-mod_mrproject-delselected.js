YUI.add('moodle-mod_mrproject-delselected', function (Y, NAME) {

// ESLint directives.
/* eslint-disable camelcase */

var SELECTORS = {
    DELACTION: 'div.commandbar a#delselected',
    SELECTBOX: 'table#meetingmanager input.meetingselect'
},
    MOD;

M.mod_mrproject = M.mod_mrproject || {};
MOD = M.mod_mrproject.delselected = {};

/**
 * Copy the selected boexs into an input parameter of the respective form
 *
 * @param {String} link
 * @param {String} baseurl
 */
MOD.collect_selection = function(link, baseurl) {

    var sellist = '';
    Y.all(SELECTORS.SELECTBOX).each(function(box) {
        if (box.get('checked')) {
            if (sellist.length > 0) {
                sellist += ',';
            }
            sellist += box.get('value');
        }
    });
    link.setAttribute('href', baseurl + '&items=' + sellist);
};

MOD.init = function(baseurl) {
    var link = Y.one(SELECTORS.DELACTION);
    if (link !== null) {
        link.on('click', function() {
            M.mod_mrproject.delselected.collect_selection(link, baseurl);
        });
    }
};

}, '@VERSION@', {"requires": ["base", "node", "event"]});
