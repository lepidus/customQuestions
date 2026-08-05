import questionsFixture from '../../fixtures/customQuestions.json';
import {
	cleanupCustomQuestions,
	customQuestionApiUrl,
	newTestRunId,
	seedCustomQuestions,
} from '../../support/customQuestions';

describe('Custom Questions publication workflow', function () {
	let questions;
	let submissionId;
	let testRunId;

	beforeEach(function () {
		testRunId = newTestRunId('workflow');
		const application = Cypress.env('defaultGenre') === 'Article Text' ? 'ojs' : 'ops';
		const submissionTitle = questionsFixture.workflowSubmissionTitle[application];
		seedCustomQuestions(testRunId, {submissionTitle}).then((result) => {
			questions = result.questionsByKey;
			submissionId = result.submissionId;
		});
	});

	afterEach(function () {
		cleanupCustomQuestions(testRunId);
	});

	it('enforces access and persists edited responses', function () {
		cy.request({
			url: customQuestionApiUrl(submissionId),
			failOnStatusCode: false,
		}).its('status').should('be.oneOf', [401, 403]);

		cy.login('amwandenga', null, 'publicknowledge');
		cy.request({
			url: customQuestionApiUrl(submissionId),
			failOnStatusCode: false,
		}).its('status').should('be.oneOf', [401, 403]);
		cy.logout();

		cy.intercept('GET', `**/customQuestionResponses/${submissionId}*`).as('loadResponses');
		cy.login('dbarnes', null, 'publicknowledge');
		cy.getCsrfToken().then(() => {
			cy.request({
				url: customQuestionApiUrl(submissionId),
				method: 'PUT',
				headers: {'X-Csrf-Token': this.csrfToken},
				body: {
					[`customQuestion-${questions.smallText.id}`]: {en: 'Must not be persisted'},
					unexpectedField: 'invalid',
				},
				failOnStatusCode: false,
			}).its('status').should('eq', 422);
		});
		cy.visit(`/index.php/publicknowledge/workflow/access/${submissionId}`);
		cy.get('[data-cy="active-modal"]').contains('Custom Questions').click();
		cy.wait('@loadResponses').its('response.statusCode').should('eq', 200);

		const smallText = questions.smallText;
		const smallTextName = `customQuestion-${smallText.id}`;
		cy.get(`input[name^="${smallTextName}"]:visible`).should('have.value', smallText.response);
		questions.checkboxes.response.forEach((response) => {
			cy.get(`input[name^="customQuestion-${questions.checkboxes.id}"][value="${response}"]`)
				.should('be.checked');
		});
		cy.get(`input[name^="customQuestion-${questions.radio.id}"][value="${questions.radio.response}"]`)
			.should('be.checked');
		cy.get(`select[id^="customQuestions-customQuestion-${questions.select.id}"]`)
			.should('have.value', String(questions.select.response));

		const editedResponse = `Edited response ${testRunId}`;
		cy.intercept('POST', `**/customQuestionResponses/${submissionId}*`).as('saveResponses');
		cy.get(`input[name^="${smallTextName}"]:visible`).clear().type(editedResponse);
		cy.get('[data-cy="active-modal"]').contains('button', 'Save').click();
		cy.wait('@saveResponses').its('response.statusCode').should('eq', 200);

		cy.intercept('GET', `**/customQuestionResponses/${submissionId}*`).as('reloadResponses');
		cy.reload();
		cy.wait('@reloadResponses').its('response.statusCode').should('eq', 200);
		cy.get(`input[name^="${smallTextName}"]:visible`).should('have.value', editedResponse);
	});
});
