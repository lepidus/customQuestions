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
        {foreach from=$customQuestions item=$customQuestion}
            {assign var="customQuestionResponse" value=$customQuestionResponses[$customQuestion->getId()]}
            {if is_null($customQuestionResponse)}
                {assign var="value" value=null}
            {else}
                {assign var="value" value=$customQuestionResponse->getValue()}
            {/if}
            {if $customQuestion->getCustomQuestionResponseType() === 'string'}
                {foreach from=$locales item=$locale key=$localeKey}
                    <div class="submissionWizard__reviewPanel__item">
                        <h4 class="submissionWizard__reviewPanel__item__header">
                            {translate key="common.withParenthesis" item=$customQuestion->getLocalizedTitle()|escape inParenthesis=$locale}
                        </h4>
                        <div
                            class="submissionWizard__reviewPanel__item__value"
                            v-html="'{$value[$localeKey]|escape}'
                                ? '{$value[$localeKey]|escape}'
                                : '{translate key="common.noneProvided"}'"
                        >
                        </div>
                    </div>
                {/foreach}
            {else}
                {assign var="possibleResponses" value=$customQuestion->getLocalizedPossibleResponses()}
                <div class="submissionWizard__reviewPanel__item">
                    <h4 class="submissionWizard__reviewPanel__item__header">
                        {$customQuestion->getLocalizedTitle()|escape}
                    </h4>
                    <div
                        class="submissionWizard__reviewPanel__item__value"
                    >
                        {if $customQuestion->getCustomQuestionResponseType() === 'int'}
                            {$possibleResponses[$value]|escape}
                        {else}
                            {foreach from=$value item=$response}
                                {$possibleResponses[$response]|escape}<br>
                            {/foreach}
                        {/if}
                    </div>
                </div>
            {/if}
        {/foreach}
    </div>
</div>
