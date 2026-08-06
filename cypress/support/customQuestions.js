const helperPath = 'plugins/generic/customQuestions/cypress/support/CustomQuestionsTestData.php';

const quote = (value) => `'${String(value).replace(/'/g, `'"'"'`)}'`;

export const newTestRunId = (scenario) => {
	return `${scenario}-${Date.now()}-${Cypress._.random(1000, 9999)}`;
};

const runHelper = (operation, testRunId, options = {}) => {
	const argumentsList = [
		'php',
		helperPath,
		operation,
		'--context-path',
		'publicknowledge',
		'--test-run-id',
		testRunId,
	];

	if (options.submissionTitle) {
		argumentsList.push('--submission-title', options.submissionTitle);
	}
	if (operation !== 'count') {
		argumentsList.push('--apply');
	}

	return cy.exec(argumentsList.map(quote).join(' '), {log: false}).then(({stdout}) => {
		return JSON.parse(stdout.trim());
	});
};

export const seedCustomQuestions = (testRunId, options = {}) => {
	return runHelper('seed', testRunId, options);
};

export const enableCustomQuestions = (testRunId) => {
	return runHelper('enable', testRunId);
};

export const cleanupCustomQuestions = (testRunId) => {
	return runHelper('cleanup', testRunId);
};

export const customQuestionApiUrl = (submissionId) => {
	return `/index.php/publicknowledge/api/v1/customQuestionResponses/${submissionId}`;
};

export const answerCustomQuestions = (questions) => {
	Object.values(questions).forEach((question) => {
		const fieldName = `customQuestion-${question.id}`;
		if ([1, 2].includes(question.type)) {
			cy.get(`input[name^="${fieldName}"][id*="-control-en"]`).clear().type(question.response);
		} else if (question.type === 3) {
			cy.get(`textarea[id^="customQuestions-${fieldName}"][id*="-control-en"]`).then(($textarea) => {
				cy.setTinyMceContent($textarea.attr('id'), question.response);
			});
		} else if (question.type === 4) {
			question.response.forEach((response) => {
				cy.get(`input[name^="${fieldName}"][value="${response}"]`).check();
			});
		} else if (question.type === 5) {
			cy.get(`input[name^="${fieldName}"][value="${question.response}"]`).check();
		} else if (question.type === 6) {
			cy.get(`select[id^="customQuestions-${fieldName}"]`).select(String(question.response));
		}
	});
};
