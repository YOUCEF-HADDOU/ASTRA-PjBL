YUI.add('moodle-mod_mrproject-studentlist', function (Y, NAME) {

// ESLint directives.
/* eslint-disable camelcase */

var CSS = {
    EXPANDED: 'expanded',
    COLLAPSED: 'collapsed'
};

M.mod_mrproject = M.mod_mrproject || {};
var MOD = M.mod_mrproject.studentlist = {};

MOD.setState = function(id, expanded) {
    var image = Y.one('#' + id);
    var content = Y.one('#list' + id);
    if (expanded) {
        content.removeClass(CSS.COLLAPSED);
        content.addClass(CSS.EXPANDED);
        image.set('src', M.util.image_url('t/expanded'));
    } else {
        content.removeClass(CSS.EXPANDED);
        content.addClass(CSS.COLLAPSED);
        image.set('src', M.util.image_url('t/collapsed'));
    }
};

MOD.toggleState = function(id) {
    var content = Y.one('#list' + id);
    var isVisible = content.hasClass(CSS.EXPANDED);
    this.setState(id, !isVisible);
};

MOD.init = function(imageid, expanded) {
    this.setState(imageid, expanded);
    Y.one('#' + imageid).on('click', function() {
        M.mod_mrproject.studentlist.toggleState(imageid);
    });
};


}, '@VERSION@', {"requires": ["base", "node", "event", "io"]});
