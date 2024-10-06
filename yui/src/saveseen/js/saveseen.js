// ESLint directives.
/* eslint-disable camelcase */

var SELECTORS = {
    //CHECKBOXES: 'table#meetingmanager form.studentselectform input.studentselect'
    CHECKBOXES: 'table#availablemeeting form.studentselectform input.studentselect'
},
    MOD;

M.mod_mrproject = M.mod_mrproject || {};
MOD = M.mod_mrproject.saveseen = {};

/**
 * Save the "seen" status.
 *
 * @param {Number} cmid the coursemodule id
 * @param {Number} appid the id of the relevant task
 * @param {Boolean} newseen
 * @param {Object} spinner The spinner icon shown while saving
 */
MOD.save_status = function(cmid, appid, newseen, spinner) {

    Y.io(M.cfg.wwwroot + '/mod/mrproject/ajax.php', {
        // The request paramaters.
        data: {
            action: 'saveseen',
            id: cmid,
            taskid: appid,
            seen: newseen,
            sesskey: M.cfg.sesskey
        },

        timeout: 5000, // 5 seconds of timeout.

        // Define the events.
        on: {
            start: function() {
                spinner.show();
            },
            success: function() {
                window.setTimeout(function() {
                    spinner.hide();
                }, 250);
            },
            failure: function(transactionid, xhr) {
                var msg = {
                    name: xhr.status + ' ' + xhr.statusText,
                    message: xhr.responseText
                };
                spinner.hide();
                return new M.core.exception(msg);
            }
        },
        context: this
    });
};

MOD.init = function(cmid) {
    Y.all(SELECTORS.CHECKBOXES).each(function(box) {
        box.on('change', function() {
            var spinner = M.util.add_spinner(Y, box.ancestor('div'));
            M.mod_mrproject.saveseen.save_status(cmid, box.get('value'), box.get('checked'), spinner);
        });
    });
};
