(function() {
    if (typeof pkp === 'undefined' || typeof pkp.eventBus === 'undefined') {
        return;
    }

    var root;
    pkp.eventBus.$on('root:mounted', function(id, component) {
        root = component;
		root.autosaveSucceeded = function (autosave, response) {
			if (response.submissionId) {
				root.publication = response;
			} else if (response.dateSubmitted) {
				root.submission = response;
			} else if (Array.isArray(response)) {
				root.customQuestionResponses = response;
			}
		};
    });
}());
