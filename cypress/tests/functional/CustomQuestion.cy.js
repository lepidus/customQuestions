describe('Custom Quetions plugin tests', function() {
	it('Creates and exercises a custom question', function() {
		cy.login('admin', 'admin', 'publicknowledge');

		cy.get('.app__nav a').contains('Website').click();
		cy.get('button[id="plugins-button"]').click();

		cy.get('input[id^="select-cell-customquestionsplugin-enabled"]').check();
		cy.get('input[id^="select-cell-customquestionsplugin-enabled"]').should('be.checked');
		cy.waitJQuery();

		cy.get('tr[id*="customquestionsplugin"] a.show_extras').click();
		cy.get('a[id*="customquestionsplugin-settings"]').click();

		cy.get('a:contains("Create New Question")').click();
		cy.wait(2000);
		cy.get('textarea[name="title[en]"]').then(node => {
			cy.setTinyMceContent(node.attr('id'), 'Here is my custom question.');
		});
		cy.get('textarea[name="description[en]"]').then(node => {
			cy.setTinyMceContent(node.attr('id'), 'Here is my custom description.');
		});
		cy.get('textarea[name="description[en]"]').click({force: true});
		cy.get('input[name="required"]').check();
		cy.get('select[name="questionType"]').select('4');
		cy.get('a:contains("Add Item")').click();
		cy.wait(500);
		cy.get('input[name="newRowId[possibleResponse][en]"]:last').type('option 1');
		cy.get('a:contains("Add Item")').click();
		cy.wait(500);
		cy.get('input[name="newRowId[possibleResponse][en]"]:last').type('option 2');
		cy.get('a:contains("Add Item")').click();
		cy.wait(500);
		cy.get('input[name="newRowId[possibleResponse][en]"]:last').type('option 3');
		cy.get('form[id="customQuestionForm"] button[id^="submitFormButton-"]').click({force: true});
		cy.get('div:contains(\'Your changes have been saved.\')');
		cy.waitJQuery();
		cy.wait(500);
		cy.get('.pkp_modal_panel > .close').click();
		cy.wait(500);

		cy.get('a[id*="customquestionsplugin-settings"]').click();
		cy.get('tr[id*="customquestiongrid-row"]:contains("Here is my custom question.") a.show_extras').click();
		cy.get('tr[id*="customquestiongrid-row"]:contains("Here is my custom question.")').next().contains('Edit').click();
		cy.get('textarea[name="title[en]"]').then(node => {
			cy.setTinyMceContent(node.attr('id'), 'Edited custom question.');
		});
		cy.get('textarea[name="description[en]"]').then(node => {
			cy.setTinyMceContent(node.attr('id'), 'Edited question description.');
		});
		cy.get('textarea[name="description[en]"]').click({force: true});
		cy.get('input[name="required"]').uncheck();
		cy.get('select[name="questionType"]').select('1');
		cy.get('form[id="customQuestionForm"] button[id^="submitFormButton-"]').click({force: true});
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
});
