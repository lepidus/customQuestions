describe('Custom Quetions plugin tests', function () {

	const createCustomQuestion = (customQuestion) => {
		customQuestion.description = customQuestion.description || '';
		customQuestion.required = customQuestion.required || false;
		customQuestion.possibleResponses = customQuestion.possibleResponses || [];

		cy.get('a[id*="customquestionsplugin-settings"]').click();

		cy.get('a:contains("Create New Question")').click();
		cy.wait(2000);
		cy.get('input[name="title[en]"]').type(customQuestion.title);
		cy.get('textarea[name="description[en]"]').then(node => {
			cy.setTinyMceContent(node.attr('id'), customQuestion.description);
		});
		cy.get('textarea[name="description[en]"]').click({ force: true });
		customQuestion.required ? cy.get('input[name="required"]').check() : cy.get('input[name="required"]').uncheck();
		cy.get('select[name="questionType"]').select(customQuestion.type);
		if (customQuestion.possibleResponses.length > 0) {
			customQuestion.possibleResponses.forEach((response) => {
				cy.get('a:contains("Add Item")').click();
				cy.wait(500);
				cy.get('input[name="newRowId[possibleResponse][en]"]:last').type(response);
			});
		}
		cy.get('form[id="customQuestionForm"] button[id^="submitFormButton-"]').click({ force: true });
		cy.get('div:contains(\'Your changes have been saved.\')');
		cy.waitJQuery();
		cy.wait(500);
		cy.get('.pkp_modal_panel > .close').click();
		cy.wait(500);
	}

	const toKebabCase = (text) => {
		return text.toLowerCase().replace(/ /g, '-');
	};

	it('Creates and exercises a custom question', function () {
		const customQuestion = {
			'title': 'Here is my custom question.',
			'description': 'Here is my custom description.',
			'required': true,
			'type': '4',
			'possibleResponses': ['option 1', 'option 2', 'option 3']
		}

		cy.login('admin', 'admin', 'publicknowledge');

		cy.get('.app__nav a').contains('Website').click();
		cy.get('button[id="plugins-button"]').click();

		cy.get('input[id^="select-cell-customquestionsplugin-enabled"]').check();
		cy.get('input[id^="select-cell-customquestionsplugin-enabled"]').should('be.checked');
		cy.waitJQuery();
		cy.get('tr[id*="customquestionsplugin"] a.show_extras').click();

		createCustomQuestion(customQuestion);

		cy.get('a[id*="customquestionsplugin-settings"]').click();
		cy.get('tr[id*="customquestiongrid-row"]:contains("Here is my custom question.") a.show_extras').click();
		cy.get('tr[id*="customquestiongrid-row"]:contains("Here is my custom question.")').next().contains('Edit').click();
		cy.get('input[name="title[en]"]').type('Edited custom question.');
		cy.get('textarea[name="description[en]"]').then(node => {
			cy.setTinyMceContent(node.attr('id'), 'Edited question description.');
		});
		cy.get('textarea[name="description[en]"]').click({ force: true });
		cy.get('input[name="required"]').uncheck();
		cy.get('select[name="questionType"]').select('1');
		cy.get('form[id="customQuestionForm"] button[id^="submitFormButton-"]').click({ force: true });
		cy.get('div:contains(\'Your changes have been saved.\')');
		cy.waitJQuery();
		cy.wait(500);
		cy.get('.pkp_modal_panel > .close').click();
		cy.wait(500);

		cy.get('a[id*="customquestionsplugin-settings"]').click();
		cy.get('tr[id*="customquestiongrid-row"]:contains("Edited custom question.") a.show_extras').click();
		cy.get('tr[id*="customquestiongrid-row"]:contains("Edited custom question.")').next().contains('Delete').click();
		cy.get('div[aria-label="Confirm"] button:contains("OK")').click();

		cy.get('tr[id*="customquestiongrid-row"]:contains("Edited custom question.")').should('not.exist');
	});

	it('Displays custom questions in submission wizard', function () {
		cy.login('admin', 'admin', 'publicknowledge');

		cy.get('.app__nav a').contains('Website').click();
		cy.get('button[id="plugins-button"]').click();

		cy.get('input[id^="select-cell-customquestionsplugin-enabled"]').check();
		cy.get('input[id^="select-cell-customquestionsplugin-enabled"]').should('be.checked');
		cy.waitJQuery();
		cy.get('tr[id*="customquestionsplugin"] a.show_extras').click();

		const customQuestions = [
			{
				'title': 'Small text custom question',
				'description': 'A custom question with a small text field.',
				'required': true,
				'type': '1',
			}, {
				'title': 'Large text custom question',
				'type': '2',
			}, {
				'title': 'Text Area custom question',
				'description': 'A custom question with a text area field.',
				'required': true,
				'type': '3',
			}, {
				'title': 'Checkbox custom question',
				'type': '4',
				'possibleResponses': ['option 1', 'option 2', 'option 3']
			}, {
				'title': 'Radio input custom question',
				'description': 'A custom question with a radio input field.',
				'required': true,
				'type': '5',
				'possibleResponses': ['option 1', 'option 2', 'option 3']
			}, {
				'title': 'Select custom question',
				'type': '6',
				'possibleResponses': ['option 1', 'option 2', 'option 3']
			},
		];

		customQuestions.forEach((customQuestion) => {
			createCustomQuestion(customQuestion);
		});

		cy.login('ccorino', null, 'publicknowledge');

		cy.contains('New Submission').click();

		cy.setTinyMceContent('startSubmission-title-control', 'Custom Question Submission');
		cy.get('label:contains("English")').click();
		cy.get('input[name="submissionRequirements"]').check();
		cy.get('input[name="privacyConsent"]').check();
		cy.contains('Begin Submission').click();

		cy.contains('Make a Submission: Details');
		customQuestions.forEach((customQuestion) => {
			if ([1, 2, 3, 6].includes(parseInt(customQuestion.type))) {
				cy.get(`label[for^="customQuestions-${toKebabCase(customQuestion.title)}"]`).contains(customQuestion.title);
				if (customQuestion.required) {
					cy.get(`label[for^="customQuestions-${toKebabCase(customQuestion.title)}"]`).children('span').should('have.class', 'pkpFormFieldLabel__required');
				}
			} else {
				cy.get(`input[name="${toKebabCase(customQuestion.title)}"]`).parents('fieldset').children('legend').contains(customQuestion.title);
				if (customQuestion.required) {
					cy.get(`input[name="${toKebabCase(customQuestion.title)}"]`).parents('fieldset').children('legend').children('span').should('have.class', 'pkpFormFieldLabel__required');
				}
			}

			if (customQuestion.description) {
				cy.get(`div[id^="customQuestions-${toKebabCase(customQuestion.title)}-description"]`).contains(customQuestion.description);
			}

			if (customQuestion.type === '1') {
				cy.get(`input[name^="${toKebabCase(customQuestion.title)}"]`).should('have.attr', 'type', 'text');
				cy.get(`input[name^="${toKebabCase(customQuestion.title)}"]`).parents('.pkpFormField--sizesmall');
			} else if (customQuestion.type === '2') {
				cy.get(`input[name^="${toKebabCase(customQuestion.title)}"]`).should('have.attr', 'type', 'text');
				cy.get(`input[name^="${toKebabCase(customQuestion.title)}"]`).parents('.pkpFormField--sizelarge');
			} else if (customQuestion.type === '3') {
				cy.get(`textarea[id^="customQuestions-${toKebabCase(customQuestion.title)}-control-en"]`).should('exist');
			} else if (customQuestion.type === '4') {
				cy.get(`input[name^="${toKebabCase(customQuestion.title)}"]`).should('have.attr', 'type', 'checkbox');
				customQuestion.possibleResponses.forEach((response) => {
					cy.get(`input[name^="${toKebabCase(customQuestion.title)}"]`).next().contains(response);
				});
			} else if (customQuestion.type === '5') {
				cy.get(`input[name^="${toKebabCase(customQuestion.title)}"]`).should('have.attr', 'type', 'radio');
				customQuestion.possibleResponses.forEach((response) => {
					cy.get(`input[name^="${toKebabCase(customQuestion.title)}"]`).next().contains(response);
				});
			} else if (customQuestion.type === '6') {
				customQuestion.possibleResponses.forEach((response) => {
					cy.get(`select[id^="customQuestions-${toKebabCase(customQuestion.title)}"]`).children('option').contains(response);
				});
			}
		});
	});
});
