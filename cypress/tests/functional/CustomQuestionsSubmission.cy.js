import {
	answerCustomQuestions,
	cleanupCustomQuestions,
	newTestRunId,
	seedCustomQuestions,
} from '../../support/customQuestions';

describe('Custom Questions submission wizard', function () {
	let questions;
	let testRunId;

	beforeEach(function () {
		testRunId = newTestRunId('submission');
		seedCustomQuestions(testRunId).then((result) => {
			questions = result.questionsByKey;
		});
	});

	afterEach(function () {
		cleanupCustomQuestions(testRunId);
	});

	it('requires and saves every supported response type', function () {
		const submissionTitle = `Required Custom Question Submission [${testRunId}]`;
		cy.login('ccorino', null, 'publicknowledge');
		cy.contains('a', 'New Submission').first().click();
		cy.setTinyMceContent('startSubmission-title-control', submissionTitle);
		if (Cypress.env('defaultGenre') === 'Article Text') {
			cy.contains('label', 'Articles').click();
		}
		cy.contains('label', 'English').click();
		cy.get('input[name="submissionRequirements"]').check();
		cy.get('input[name="privacyConsent"]').check();
		cy.contains('button', 'Begin Submission').click();

		cy.contains('Make a Submission: Details').should('be.visible');
		cy.setTinyMceContent(
			'titleAbstract-abstract-control-en',
			'Checking required custom questions in submission wizard.'
		);

		Object.values(questions).forEach((question) => {
			cy.contains('label, legend', question.title).should('be.visible');
			if (question.description) {
				cy.contains(question.description).should('be.visible');
			}
			cy.contains('label, legend', question.title)
				.find('.pkpFormFieldLabel__required')
				.should('exist');
		});
		cy.get(`input[name^="customQuestion-${questions.smallText.id}"]`)
			.closest('.pkpFormField--sizesmall').should('exist');
		cy.get(`input[name^="customQuestion-${questions.largeText.id}"]`)
			.closest('.pkpFormField--sizelarge').should('exist');
		cy.get(`textarea[id^="customQuestions-customQuestion-${questions.textarea.id}"]`).should('exist');
		cy.get(`input[name^="customQuestion-${questions.checkboxes.id}"]`)
			.should('have.attr', 'type', 'checkbox');
		cy.get(`input[name^="customQuestion-${questions.radio.id}"]`)
			.should('have.attr', 'type', 'radio');
		cy.get(`select[id^="customQuestions-customQuestion-${questions.select.id}"] option`)
			.should('have.length.at.least', 3);

		cy.contains('.submissionWizard__footer button', 'Continue').click();
		cy.contains('Make a Submission: Upload Files').should('be.visible');
		const files = [{
			file: 'dummy.pdf',
			fileName: 'manuscript.pdf',
			mimeType: 'application/pdf',
			genre: Cypress.env('defaultGenre'),
		}];
		if (Cypress.env('defaultGenre') === 'Article Text') {
			cy.uploadSubmissionFiles(files);
		} else {
			cy.addSubmissionGalleys(files);
		}
		for (let step = 0; step < 3; step++) {
			cy.contains('.submissionWizard__footer button', 'Continue').click();
		}

		cy.contains('Make a Submission: Review').should('be.visible');
		Object.values(questions).forEach((question) => {
			cy.contains('.submissionWizard__reviewPanel__item h4', question.title)
				.closest('.submissionWizard__reviewPanel__item')
				.contains('This field is required.');
		});
		cy.contains('button', 'Submit').should('be.disabled');

		cy.contains('.submissionWizard__reviewPanel h3', 'Custom questions')
			.closest('.submissionWizard__reviewPanel')
			.find('.submissionWizard__reviewPanel__edit')
			.contains('Edit').click();
		cy.intercept('POST', '**/customQuestionResponses/*').as('saveResponses');
		answerCustomQuestions(questions);
		cy.contains('.submissionWizard__footer button', 'Continue').click();
		cy.wait('@saveResponses').its('response.statusCode').should('eq', 200);
		for (let step = 0; step < 3; step++) {
			cy.contains('.submissionWizard__footer button', 'Continue').click();
		}

		cy.contains('Make a Submission: Review').should('be.visible');
		Object.values(questions).forEach((question) => {
			const expected = question.type === 4
				? question.response.map((index) => question.possibleResponses[index]).join(', ')
				: [5, 6].includes(question.type)
					? question.possibleResponses[question.response]
					: question.response;
			cy.contains('.submissionWizard__reviewPanel__item h4', question.title)
				.closest('.submissionWizard__reviewPanel__item')
				.contains(expected);
		});
		cy.contains('button', 'Submit').should('be.enabled').click();
		cy.get('div[role="dialog"]').contains('button', 'Submit').click();
		cy.contains('Submission complete').should('be.visible');
	});
});
