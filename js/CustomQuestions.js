(function () {
    if (typeof pkp === 'undefined' || typeof pkp.eventBus === 'undefined') {
        return;
    }

    pkp.eventBus.$on('root:mounted', function (id, component) {
        var autosaveSucceeded = component.autosaveSucceeded;

        component.autosaveSucceeded = function (autosave, response) {
            if (typeof autosaveSucceeded === 'function') {
                autosaveSucceeded.call(this, autosave, response);
            }
            if (Array.isArray(response)) {
                this.customQuestionResponses = response;
            }
        };
    });
}());
