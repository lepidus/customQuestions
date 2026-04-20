(function() {
    if (typeof pkp === 'undefined' || typeof pkp.eventBus === 'undefined') {
        return;
    }

    function getCustomQuestionsForm(root, formId) {
        if (!root || !Array.isArray(root.steps)) {
            return null;
        }

        var targetFormId = formId || 'customQuestions';
        for (var stepIndex = 0; stepIndex < root.steps.length; stepIndex++) {
            var step = root.steps[stepIndex];
            if (!Array.isArray(step.sections)) {
                continue;
            }

            for (var sectionIndex = 0; sectionIndex < step.sections.length; sectionIndex++) {
                var section = step.sections[sectionIndex];
                if (
                    section.type === 'form'
                    && section.form
                    && section.form.id === targetFormId
                ) {
                    return section.form;
                }
            }
        }

        return null;
    }

    function mapLegacyAutosavePayload(root, payload) {
        if (!payload || !payload.data) {
            return payload;
        }

        var form = getCustomQuestionsForm(root, payload.id);
        if (!form || !Array.isArray(form.fields)) {
            return payload;
        }

        var normalizedData = Object.assign({}, payload.data);

        form.fields.forEach(function(field) {
            if (
                !field
                || !field.name
                || !field.legacyName
                || Object.prototype.hasOwnProperty.call(normalizedData, field.name)
                || !Object.prototype.hasOwnProperty.call(normalizedData, field.legacyName)
            ) {
                return;
            }

            normalizedData[field.name] = normalizedData[field.legacyName];
        });

        return Object.assign({}, payload, {
            data: normalizedData,
        });
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

        if (
            typeof root.restoreStoredAutosave === 'function'
            && !root._customQuestionsRestoreStoredAutosaveWrapped
        ) {
            var originalRestoreStoredAutosave = root.restoreStoredAutosave.bind(root);
            root.restoreStoredAutosave = function(payload) {
                return originalRestoreStoredAutosave(mapLegacyAutosavePayload(root, payload));
            };
            root._customQuestionsRestoreStoredAutosaveWrapped = true;
        }
    });
}());
