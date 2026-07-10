(function () {
    if (typeof pkp === 'undefined' || !pkp.registry) {
        return;
    }

    pkp.registry.registerComponent('CustomQuestionsWorkflowForm', {
        props: {
            apiUrl: {
                type: String,
                required: true,
            },
        },
        data: function () {
            return {
                form: null,
            };
        },
        template: [
            '<div class="-m-5">',
            '  <pkp-form v-if="form" v-bind="form" @set="setForm"></pkp-form>',
            '  <pkp-spinner v-else></pkp-spinner>',
            '</div>',
        ].join(''),
        created: function () {
            $.ajax({
                url: this.apiUrl,
                method: 'GET',
                context: this,
                headers: {
                    'X-Csrf-Token': pkp.currentUser.csrfToken,
                },
                success: function (form) {
                    this.form = form;
                },
                error: this.ajaxErrorCallback,
            });
        },
        methods: {
            setForm: function (formId, data) {
                this.form = Object.assign({}, this.form, data);
            },
        },
    });

    pkp.registry.storeExtendFn('workflow', 'getMenuItems', function (items, args) {
        var publicationMenu = items.find(function (item) {
            return item.key === 'publication';
        });

        if (!publicationMenu || !args.submission) {
            return items;
        }

        publicationMenu.items.push({
            key: 'publication_customQuestions',
            label: args.pageInitConfig.customQuestionsLabel,
            state: {
                primaryMenuItem: 'publication',
                secondaryMenuItem: 'customQuestions',
                title: args.pageInitConfig.customQuestionsLabel,
            },
        });

        return items;
    });

    pkp.registry.storeExtendFn('workflow', 'getPrimaryItems', function (items, args) {
        if (
            args.selectedMenuState.primaryMenuItem !== 'publication'
            || args.selectedMenuState.secondaryMenuItem !== 'customQuestions'
        ) {
            return items;
        }

        return [{
            component: 'CustomQuestionsWorkflowForm',
            props: {
                apiUrl: args.pageInitConfig.customQuestionsApiUrl.replace(
                    '__submissionId__',
                    args.submission.id
                ),
            },
        }];
    });
}());
