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
                class="submissionWizard__reviewPanel__item__value"
            >
                <template v-if="!customQuestionResponses.find(response => response.customQuestionId == customQuestion.id)?.value">
                    {translate key="common.noneProvided"}
                </template>
                <template v-else
                    v-for="response in customQuestionResponses"
                    v-if="response.customQuestionId == customQuestion.id"
                >
                    <template v-if="response.responseType === 'string'">
                        {{
                            localize(response.value)
                            ? localize(response.value)
                            : '{translate key="common.noneProvided"}'
                        }}
                    </template>
                    <template v-else-if="response.responseType === 'array'">
                        {{
                            localize(customQuestion.possibleResponses)
                            .filter((possibleResponse, id) => response.value.includes(id.toString()))
                            .join(__('common.commaListSeparator'))
                        }}
                    </template>
                    <template v-else>
                        {{ localize(customQuestion.possibleResponses)[response.value] }}
                    </template>
                </template>
            </div>
        </div>
    </div>
</div>
