describe('Custom Quetions plugin tests', function () {

	let customQuestions;

	before(function () {
		customQuestions = [
			{
				title: 'Small text custom question',
				description: 'A custom question with a small text field.',
				required: true,
				type: '1',
				response: 'response',
			},
			{
				title: 'Large text custom question',
				type: '2',
				response: 'Large text response',
			},
			{
				title: 'Text Area custom question',
				description: 'A custom question with a text area field.',
				required: true,
				type: '3',
				response: 'Text area response',
			},
			{
				title: 'Checkbox custom question',
				type: '4',
				possibleResponses: ['option 1', 'option 2', 'option 3'],
				response: [0, 2],
			},
			{
				title: 'Radio input custom question',
				description: 'A custom question with a radio input field.',
				required: true,
				type: '5',
				possibleResponses: ['option 1', 'option 2', 'option 3'],
				response: 1,
			},
			{
				title: 'Select custom question',
				type: '6',
				possibleResponses: ['option 1', 'option 2', 'option 3'],
				response: 'option 3',
			},
		];
	});

	const createCustomQuestion = (customQuestion) => {
		customQuestion.description = customQuestion.description || '';
		customQuestion.required = customQuestion.required || false;
		customQuestion.possibleResponses = customQuestion.possibleResponses || [];

		cy.get('a[id*="customquestionsplugin-settings"]').click();

		cy.get('a:contains("Create New Question")').click();
		cy.wait(2000);
		cy.get('input[name="title[en]"]').type(customQuestion.title);
		cy.get('textarea[name="description[en]"]').then((node) => {
			cy.setTinyMceContent(node.attr('id'), customQuestion.description);
		});
		cy.get('textarea[name="description[en]"]').click({ force: true });
		customQuestion.required
			? cy.get('input[name="required"]').check()
			: cy.get('input[name="required"]').uncheck();
		cy.get('select[name="questionType"]').select(customQuestion.type);
		if (customQuestion.possibleResponses.length > 0) {
			customQuestion.possibleResponses.forEach((response) => {
				cy.get('a:contains("Add Item")').click();
				cy.wait(500);
				cy.get('input[name="newRowId[possibleResponse][en]"]:last').type(response);
			});
		}
		cy.get('form[id="customQuestionForm"] button[id^="submitFormButton-"]').click({ force: true });
		cy.get("div:contains('Your changes have been saved.')");
		cy.waitJQuery();
		cy.wait(500);
		cy.get('.pkp_modal_panel > .close').click();
		cy.wait(500);

		cy.get('a[id*="customquestionsplugin-settings"]').click();
		cy.contains('tr[id*="customquestiongrid-row"] .label', customQuestion.title)
			.closest('tr[id*="customquestiongrid-row"]')
			.invoke('attr', 'id')
			.then((rowId) => {
				customQuestion.id = Number(rowId.match(/customquestiongrid-row-(\d+)/)[1]);
			});
		cy.get('.pkp_modal_panel > .close').click();
		cy.wait(500);
		return cy.wrap(customQuestion);
	};

	it('Creates and exercises a custom question', function () {
		cy.login('admin', 'admin', 'publicknowledge');

		cy.get('.app__nav a').contains('Website').click();
		cy.get('button[id="plugins-button"]').click();

		cy.get('input[id^="select-cell-customquestionsplugin-enabled"]').check();
		cy.get('input[id^="select-cell-customquestionsplugin-enabled"]').should('be.checked');
		cy.waitJQuery();
		cy.get('tr[id*="customquestionsplugin"] a.show_extras').click();

		createCustomQuestion({
			title: 'Here is my custom question.',
			description: 'Question description.',
			required: true,
			type: '4',
			possibleResponses: ['option 1', 'option 2', 'option 3'],
		});

		cy.get('a[id*="customquestionsplugin-settings"]').click();
		cy.get('tr[id*="customquestiongrid-row"]:contains("Here is my custom question.") a.show_extras').click();
		cy.get('tr[id*="customquestiongrid-row"]:contains("Here is my custom question.")').next().contains('Edit').click();
		cy.get('input[name="title[en]"]').clear();
		cy.get('input[name="title[en]"]').type('Edited custom question.');
		cy.get('textarea[name="description[en]"]').then((node) => {
			cy.setTinyMceContent(node.attr('id'), 'Edited question description.');
		});
		cy.get('textarea[name="description[en]"]').click({ force: true });
		cy.get('input[name="required"]').uncheck();
		cy.get('select[name="questionType"]').select('1');
		cy.get('form[id="customQuestionForm"] button[id^="submitFormButton-"]').click({ force: true });
		cy.get("div:contains('Your changes have been saved.')");
		cy.waitJQuery();
		cy.wait(500);
		cy.get('.pkp_modal_panel > .close').click();
		cy.wait(500);

		cy.get('a[id*="customquestionsplugin-settings"]').click();
		cy.get('tr[id*="customquestiongrid-row"]:contains("Edited custom question.") a.show_extras').click();
		cy.get('tr[id*="customquestiongrid-row"]:contains("Edited custom question.")').next().contains('Delete').click();
		cy.get('div[aria-label="Confirm"] button:contains("OK")').click();

		cy.get('tr[id*="customquestiongrid-row"]:contains("Edited custom question.")').should('not.exist');
		cy.get('.pkp_modal_panel > .close').click();

		customQuestions.forEach((customQuestion) => {
			createCustomQuestion(customQuestion);
		});
	});

	it('Displays custom questions in submission wizard', function () {
		cy.login('ccorino', null, 'publicknowledge');

		cy.contains('New Submission').click();

		cy.setTinyMceContent(
			'startSubmission-title-control',
			'Custom Question Submission'
		);
		if (Cypress.env('defaultGenre') === 'Article Text') {
			cy.get('label:contains("Articles")').click();
		}
		cy.get('label:contains("English")').click();
		cy.get('input[name="submissionRequirements"]').check();
		cy.get('input[name="privacyConsent"]').check();
		cy.contains('Begin Submission').click();

		cy.contains('Make a Submission: Details');

		cy.setTinyMceContent('titleAbstract-abstract-control-en', 'Checking custom questions in submission wizard.');

		customQuestions.forEach((customQuestion) => {
			cy.get(`label[for^="customQuestions-${customQuestion.id}"], legend`).contains(customQuestion.title);

			if (customQuestion.description) {
				cy.get(`div[id^="customQuestions-${customQuestion.id}"][id*="description"]`).contains(customQuestion.description);
			}

			if (customQuestion.required) {
				cy.get(`label[for^="customQuestions-${customQuestion.id}"] span, legend:contains(${customQuestion.title}) span`).should('have.class', 'pkpFormFieldLabel__required');
			}

			if (customQuestion.type === '1') {
				cy.get(`input[name^="${customQuestion.id}"]`).should('have.attr', 'type', 'text');
				cy.get(`input[name^="${customQuestion.id}"]`).parents('.pkpFormField--sizesmall');
				cy.get(`input[name^="${customQuestion.id}"][id*="-control-en"]`).clear().type(customQuestion.response);
			}
			if (customQuestion.type === '2') {
				cy.get(`input[name^="${customQuestion.id}"]`).should('have.attr', 'type', 'text');
				cy.get(`input[name^="${customQuestion.id}"]`).parents('.pkpFormField--sizelarge');
				cy.get(`input[name^="${customQuestion.id}"][id*="-control-en"]`).clear().type(customQuestion.response);

			}
			if (customQuestion.type === '3') {
				cy.get(`textarea[id^="customQuestions-${customQuestion.id}"][id*="-control-en"]`).then(($textarea) => {
					const fieldId = $textarea.attr('id');
					cy.setTinyMceContent(fieldId, customQuestion.response);
				});
			}
			if (customQuestion.type === '4') {
				cy.get(`input[name^="${customQuestion.id}"]`).should('have.attr', 'type', 'checkbox');
				customQuestion.possibleResponses.forEach((response) => {
					cy.get(`input[name^="${customQuestion.id}"]`).next().contains(response);
				});
				customQuestion.response.forEach((response) => {
					cy.get(`input[name^="${customQuestion.id}"][value=${response}]`).check();
				});
			}
			if (customQuestion.type === '5') {
				cy.get(`input[name^="${customQuestion.id}"]`).should('have.attr', 'type', 'radio');
				customQuestion.possibleResponses.forEach((response) => {
					cy.get(`input[name^="${customQuestion.id}"]`).next().contains(response);
				});
				cy.get(`input[name^="${customQuestion.id}"][value=${customQuestion.response}]`).check();
			}
			if (customQuestion.type === '6') {
				customQuestion.possibleResponses.forEach((response) => {
					cy.get(`select[id^="customQuestions-${customQuestion.id}"]`).children('option').contains(response);
				});
				cy.get(`select[id^="customQuestions-${customQuestion.id}"]`).select(customQuestion.response);
			}
		});

		cy.get('.submissionWizard__footer button').contains('Continue').click();

		cy.contains('Make a Submission: Upload Files');
		cy.get('h2').contains('Upload Files');
		cy.get('h2').contains('Files');

		let files = [{
			'file': 'dummy.pdf',
			'fileName': 'manuscript.pdf',
			'mimeType': 'application/pdf',
			'genre': Cypress.env('defaultGenre')
		}];

		if (Cypress.env('defaultGenre') === 'Article Text') {
			cy.uploadSubmissionFiles(files);
		} else {
			cy.addSubmissionGalleys(files);
		}

		cy.get('.submissionWizard__footer button').contains('Continue').click();
		cy.get('.submissionWizard__footer button').contains('Continue').click();
		cy.get('.submissionWizard__footer button').contains('Continue').click();

		cy.contains('Make a Submission: Review');

		customQuestions.forEach((customQuestion) => {
			cy.get('h3').contains('Custom questions')
				.parents('.submissionWizard__reviewPanel')
				.find('h4').then(($h4) => {
					if ([1, 2, 3].includes(customQuestion.type)) {
						cy.wrap($h4).contains(customQuestion.title).siblings('.submissionWizard__reviewPanel__item__value').contains(customQuestion.response);
					}
					if ([5, 6].includes(customQuestion.type)) {
						cy.wrap($h4).contains(customQuestion.title).siblings('.submissionWizard__reviewPanel__item__value').contains(customQuestion.possibleResponses[customQuestion.response]);
					}
					if (customQuestion.type === '4') {
						let responses = [];
						customQuestion.response.forEach((response) => {
							responses.push(customQuestion.possibleResponses[response]);
						});
						cy.wrap($h4).contains(customQuestion.title).siblings('.submissionWizard__reviewPanel__item__value').contains(responses.join(', '));
					}
				});
		});

		cy.get('button:contains("Submit")').click();
		cy.get('.modal__panel button').contains('Submit').click();
	});

	it('Checks custom questions in publication workflow', function () {
		cy.findSubmissionAsEditor('dbarnes', null, 'Corino');
		cy.get('#publication-button').click();
		cy.get('#customQuestions-button').click();

		customQuestions.forEach((customQuestion) => {
			if ([1, 2].includes(customQuestion.type)) {
				cy.get(`input[name^="${customQuestion.id}"][id*="-control-en"]`).should('have.value', customQuestion.response)
			}
			if (customQuestion.type === '3') {
				cy.get(`textarea[id^="customQuestions-${customQuestion.id}"][id*="-control-en"]`).then(($textarea) => {
					const fieldId = $textarea.attr('id');
					cy.getTinyMceContent(fieldId).should('eq', `<p>${customQuestion.response}</p>`);
				});
			}
			if (customQuestion.type === '4') {
				customQuestion.response.forEach((response) => {
					cy.get(`input[name^="${customQuestion.id}"][value=${response}]`).should('be.checked');
				});
			}
			if (customQuestion.type === '5') {
				cy.get(`input[name^="${customQuestion.id}"][value=${customQuestion.response}]`).should('be.checked');
			}
			if (customQuestion.type === '6') {
				cy.get(`select[id^="customQuestions-${customQuestion.id}"] option:selected`).should('have.attr', 'label', customQuestion.response);
			}
		});
	});
});
