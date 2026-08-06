import {
	cleanupCustomQuestions,
	enableCustomQuestions,
	newTestRunId,
} from '../../support/customQuestions';

const customQuestionsGrid = '#customQuestionGridUrlGridContainer:visible';

const findQuestionRow = (title) => {
	return cy.get(customQuestionsGrid)
		.contains('tr[id*="customquestiongrid-row"]:visible .label', title)
		.closest('tr[id*="customquestiongrid-row"]');
};

describe('Custom Questions administration', function () {
	let testRunId;

	beforeEach(function () {
		testRunId = newTestRunId('admin');
		cy.viewport(1280, 1200);
	});

	afterEach(function () {
		cleanupCustomQuestions(testRunId);
	});

	it('enables the plugin without breaking the application', function () {
		// Open the plugins list
		cy.login('admin', 'admin', 'publicknowledge');
		cy.visit('/index.php/publicknowledge/management/settings/website');
		cy.get('#plugins-button').click();

		// Disable the plugin when the dataset already has it enabled
		cy.get('input[id^="select-cell-customquestionsplugin-enabled"]')
			.then(($checkbox) => {
				if ($checkbox.is(':checked')) {
					cy.wrap($checkbox).uncheck();
					cy.contains('button', 'OK').click();
				}
			});

		// Enable the plugin through the interface
		cy.get('input[id^="select-cell-customquestionsplugin-enabled"]').should('not.be.checked').check();
		cy.get('input[id^="select-cell-customquestionsplugin-enabled"]').should('be.checked');

		// Confirm that the enabled plugin exposes its settings
		cy.visit('/index.php/publicknowledge/management/settings/website');
		cy.get('#plugins-button').click();
		cy.get('tr[id*="customquestionsplugin"] a.show_extras').click();
		cy.get('a[id*="customquestionsplugin-settings"]:visible').should('exist');
	});

	it('creates, edits and deletes a custom question', function () {
		const originalTitle = `Administration question [${testRunId}]`;
		const editedTitle = `Edited administration question [${testRunId}]`;

		// Open the custom questions settings
		enableCustomQuestions(testRunId).its('operation').should('equal', 'enable');
		cy.login('admin', 'admin', 'publicknowledge');
		cy.visit('/index.php/publicknowledge/management/settings/website');
		cy.get('#plugins-button').click();
		cy.get('tr[id*="customquestionsplugin"] a.show_extras').click();
		cy.get('a[id*="customquestionsplugin-settings"]:visible').click();
		cy.get(customQuestionsGrid).should('be.visible');
		cy.intercept('GET', '**/grid/fetch-row?*').as('refreshQuestionRow');

		// Create a required text question
		cy.contains('a', 'Create New Question').click();
		cy.get('#customQuestionForm').should('be.visible');
		cy.get('input[name="title[en]"]').type(originalTitle);
		cy.get('textarea[name="description[en]"]').then(($textarea) => {
			cy.setTinyMceContent($textarea.attr('id'), 'Administration question description.');
		});
		cy.contains('label', 'Required to complete item')
			.scrollIntoView({offset: {top: -200, left: 0}})
			.should('be.visible')
			.click({scrollBehavior: false});
		cy.get('input[name="required"]').should('be.checked');
		cy.get('select[name="questionType"]').select('Single word text box');
		cy.get('#customQuestionForm button[id^="submitFormButton-"]').click();
		cy.wait('@refreshQuestionRow').its('response.statusCode').should('equal', 200);
		cy.contains('Your changes have been saved.').should('be.visible');
		cy.get('#customQuestionForm').should('not.exist');
		findQuestionRow(originalTitle).should('be.visible');

		// Edit the question from the refreshed grid
		findQuestionRow(originalTitle).find('a.show_extras').click();
		findQuestionRow(originalTitle).next().contains('a', 'Edit').click();
		cy.get('#customQuestionForm').should('be.visible');
		cy.get('input[name="title[en]"]').clear().type(editedTitle);
		cy.contains('label', 'Required to complete item')
			.scrollIntoView({offset: {top: -200, left: 0}})
			.should('be.visible')
			.click({scrollBehavior: false});
		cy.get('input[name="required"]').should('not.be.checked');
		cy.get('select[name="questionType"]').select('Single line text box');
		cy.get('#customQuestionForm button[id^="submitFormButton-"]').click();
		cy.wait('@refreshQuestionRow').its('response.statusCode').should('equal', 200);
		cy.contains('Your changes have been saved.').should('be.visible');
		cy.get('#customQuestionForm').should('not.exist');
		findQuestionRow(editedTitle).should('be.visible');

		// Delete the edited question
		findQuestionRow(editedTitle).find('a.show_extras').click();
		findQuestionRow(editedTitle).next().contains('a', 'Delete').click();
		cy.contains('button', 'OK').click();
		cy.get(customQuestionsGrid)
			.contains('tr[id*="customquestiongrid-row"]:visible .label', editedTitle)
			.should('not.exist');
	});
});
