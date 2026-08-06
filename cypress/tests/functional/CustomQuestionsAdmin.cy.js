import {
	cleanupCustomQuestions,
	enableCustomQuestions,
	newTestRunId,
} from '../../support/customQuestions';

const findQuestionRow = (title) => {
	return cy.contains('tr[id*="customquestiongrid-row"]:visible .label', title)
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
		cy.login('admin', 'admin', 'publicknowledge');
		cy.visit('/index.php/publicknowledge/management/settings/website');
		cy.get('#plugins-button').click();
		cy.get('input[id^="select-cell-customquestionsplugin-enabled"]')
			.then(($checkbox) => {
				if ($checkbox.is(':checked')) {
					cy.wrap($checkbox).uncheck();
					cy.contains('button', 'OK').click();
				}
			});
		cy.get('input[id^="select-cell-customquestionsplugin-enabled"]').should('not.be.checked').check();
		cy.get('input[id^="select-cell-customquestionsplugin-enabled"]').should('be.checked');

		cy.visit('/index.php/publicknowledge/management/settings/website');
		cy.get('#plugins-button').click();
		cy.get('tr[id*="customquestionsplugin"] a.show_extras').click();
		cy.get('a[id*="customquestionsplugin-settings"]:visible').should('exist');
	});

	it('creates, edits and deletes a custom question', function () {
		const originalTitle = `Administration question [${testRunId}]`;
		const editedTitle = `Edited administration question [${testRunId}]`;

		enableCustomQuestions(testRunId).its('operation').should('equal', 'enable');
		cy.login('admin', 'admin', 'publicknowledge');
		cy.visit('/index.php/publicknowledge/management/settings/website');
		cy.get('#plugins-button').click();
		cy.get('tr[id*="customquestionsplugin"] a.show_extras').click();
		cy.get('a[id*="customquestionsplugin-settings"]:visible').click();

		cy.contains('a', 'Create New Question').click();
		cy.get('#customQuestionForm').should('be.visible');
		cy.get('input[name="title[en]"]').type(originalTitle);
		cy.get('textarea[name="description[en]"]').then(($textarea) => {
			cy.setTinyMceContent($textarea.attr('id'), 'Administration question description.');
		});
		cy.get('input[name="required"]').then(($input) => {
			$input.prop('checked', true).trigger('change');
		});
		cy.get('select[name="questionType"]').then(($select) => {
			$select.val('1').trigger('change');
		});
		cy.get('#customQuestionForm button[id^="submitFormButton-"]').click();
		cy.contains('Your changes have been saved.').should('be.visible');
		cy.get('button.DialogClose:visible').last().click();

		cy.get('a[id*="customquestionsplugin-settings"]:visible').click();
		findQuestionRow(originalTitle).find('a.show_extras').click();
		findQuestionRow(originalTitle).next().contains('a', 'Edit').click();
		cy.get('#customQuestionForm').should('be.visible');
		cy.get('input[name="title[en]"]').clear().type(editedTitle);
		cy.get('input[name="required"]').then(($input) => {
			$input.prop('checked', false).trigger('change');
		});
		cy.get('select[name="questionType"]').then(($select) => {
			$select.val('2').trigger('change');
		});
		cy.get('#customQuestionForm button[id^="submitFormButton-"]').click();
		cy.contains('Your changes have been saved.').should('be.visible');
		cy.get('button.DialogClose:visible').last().click();

		cy.get('a[id*="customquestionsplugin-settings"]:visible').click();
		findQuestionRow(editedTitle).find('a.show_extras').click();
		findQuestionRow(editedTitle).next().contains('a', 'Delete').click();
		cy.contains('button', 'OK').click();
		cy.contains('tr[id*="customquestiongrid-row"]:visible .label', editedTitle).should('not.exist');
	});
});
