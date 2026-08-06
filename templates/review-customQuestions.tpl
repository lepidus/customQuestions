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
            <template v-for="fieldName in ['customQuestion-' + customQuestion.id]">
                <template v-if="errors[fieldName]">
                    <template v-if="Array.isArray(errors[fieldName])">
                        <notification
                            v-for="(error, i) in errors[fieldName]"
                            :key="fieldName + '-' + i"
                            type="warning"
                        >
                            <icon icon="Error" class="h-5 w-5"></icon>
                            {{ error }}
                        </notification>
                    </template>
                    <template v-else v-for="(localizedErrors, localeKey) in errors[fieldName]">
                        <notification
                            v-for="(error, i) in localizedErrors"
                            :key="fieldName + '-' + localeKey + '-' + i"
                            type="warning"
                        >
                            <icon icon="Error" class="h-5 w-5"></icon>
                            {{ error }}
                        </notification>
                    </template>
                </template>
            </template>
            <h4 class="submissionWizard__reviewPanel__item__header">
                {{ localize(customQuestion.title) }}
            </h4>
                <template
                    v-for="response in [customQuestionResponses.find(
                        item => item.customQuestionId == customQuestion.id
                    )]"
                >
                    <div
                        v-if="!response
                            || response.value === null
                            || response.value === ''
                            || (Array.isArray(response.value) && !response.value.length)"
                        class="submissionWizard__reviewPanel__item__value"
                    >
                        {translate key="common.noneProvided"}
                    </div>
                    <div
                        v-else-if="response.responseType === 'string'"
                        class="submissionWizard__reviewPanel__item__value"
                        v-html="localize(response.value)
                            ? localize(response.value)
                            : '{translate key="common.noneProvided"}'"
                    ></div>
                    <div v-else class="submissionWizard__reviewPanel__item__value">
                        <template v-if="response.responseType === 'array'">
                            {{
                                localize(customQuestion.possibleResponses)
                                .filter((possibleResponse, id) => response.value.includes(id)
                                    || response.value.includes(id.toString()))
                                .join('{translate key="common.commaListSeparator"}')
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
