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

    function installWorkflowExtensions(store) {
        if (!store || store.customQuestionsExtensionsInstalled) {
            return false;
        }
        store.customQuestionsExtensionsInstalled = true;
        var config = pkp.customQuestions;
        store.Components.CustomQuestionsWorkflowForm = pkp.registry.getComponent(
            'CustomQuestionsWorkflowForm'
        );

        store.extender.extendFn('getMenuItems', function (items, args) {
            var publicationMenu = items.find(function (item) {
                return item.key === 'publication';
            });

            if (!publicationMenu || !args.submission) {
                return items;
            }

            publicationMenu.items.push({
                key: 'publication_customQuestions',
                label: config.label,
                state: {
                    primaryMenuItem: 'publication',
                    secondaryMenuItem: 'customQuestions',
                    title: config.label,
                },
            });

            return items;
        });

        store.extender.extendFn('getPrimaryItems', function (items, args) {
            if (
                args.selectedMenuState.primaryMenuItem !== 'publication'
                || args.selectedMenuState.secondaryMenuItem !== 'customQuestions'
            ) {
                return items;
            }

            return [{
                component: 'CustomQuestionsWorkflowForm',
                props: {
                    apiUrl: config.apiUrl.replace(
                        '__submissionId__',
                        args.submission.id
                    ),
                },
            }];
        });

        if (store.submission) {
            store.submission = Object.assign({}, store.submission);
        }
        return true;
    }

    pkp.registry.storeExtend('workflow', function (context) {
        Promise.resolve().then(function () {
            installWorkflowExtensions(context.store);
        });
    });

    pkp.eventBus.$on('root:mounted', function () {
        try {
            installWorkflowExtensions(pkp.registry.getPiniaStore('workflow'));
        } catch (error) {
            // The workflow store is created lazily when a submission is opened.
        }
    });
}());
