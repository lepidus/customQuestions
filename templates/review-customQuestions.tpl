<div class="submissionWizard__reviewPanel">
    <div class="submissionWizard__reviewPanel__header">
        <h3 id="review-plugin-custom-questions">
            {translate key="plugins.generic.customQuestions.submissionWizard.name"}
        </h3>
        <pkp-button
            aria-describedby="review-plugin-custom-questions"
            class="submissionWizard__reviewPanel__edit"
            @click="openStep('{$step.id}')"
        >
            {translate key="common.edit"}
        </pkp-button>
    </div>
    <div class="submissionWizard__reviewPanel__body">
        <div
            v-for="customQuestion in customQuestions"
            class="submissionWizard__reviewPanel__item"
        >
            <h4 class="submissionWizard__reviewPanel__item__header">
                {{ localize(customQuestion.title) }}
            </h4>
                <div
                    v-if="!customQuestionResponses.find(response => response.customQuestionId == customQuestion.id)?.value"
                    class="submissionWizard__reviewPanel__item__value"
                >
                    {translate key="common.noneProvided"}
                </div>
                <template v-else
                    v-for="response in customQuestionResponses"
                    v-if="response.customQuestionId == customQuestion.id"
                >
                    <div
                        v-if="response.responseType === 'string'"
                        class="submissionWizard__reviewPanel__item__value"
                        v-html="localize(response.value)
                            ? localize(response.value)
                            : '{translate key="common.noneProvided"}'"
                    ></div>
                    <div v-else class="submissionWizard__reviewPanel__item__value">
                        <template v-if="response.responseType === 'array'">
                            {{
                                localize(customQuestion.possibleResponses)
                                .filter((possibleResponse, id) => response.value.includes(id.toString()))
                                .join(__('common.commaListSeparator'))
                            }}
                        </template>
                        <template v-else-if="response.responseType === 'int'">
                            {{ localize(customQuestion.possibleResponses)[response.value] }}
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
