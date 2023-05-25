<script>
    $(function() {ldelim}
        $('#customQuestionForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
    {rdelim});
</script>

<script type="text/javascript">
    function togglePossibleResponses(newValue, multipleResponsesQuestionTypesString) {ldelim}
        if (multipleResponsesQuestionTypesString.indexOf(';'+newValue+';') != -1) {ldelim}
            document.getElementById('customQuestionForm').addResponse.disabled = false;
        {rdelim} else {ldelim}
            if (document.getElementById('customQuestionForm').addResponse.disabled == false) {ldelim}
            alert({translate|json_encode key="plugins.generic.customQuestions.changeType"});
        {rdelim}
            document.getElementById('customQuestionForm').addResponse.disabled = true;
        {rdelim}
    {rdelim}
</script>

<form
    class="pkp_form"
    id="customQuestionForm"
    method="post"
    action="{url router=\PKP\core\PKPApplication::ROUTE_COMPONENT
        component="plugins.generic.customQuestions.controllers.grid.CustomQuestionGridHandler"
        op="updateCustomQuestion"
        anchor="possibleResponses"
    }"
>
    {csrf}
    {fbvElement id="customQuestionId" type="hidden" name="customQuestionId" value=$customQuestionId}

    {include file="controllers/notification/inPlaceNotification.tpl" notificationId="customQuestionNotification"}

    {fbvFormArea id="customQuestionForm"}

        {fbvFormSection title="plugins.generic.customQuestions.title" required=true for="title"}
            {fbvElement type="textarea" id="title" value=$title multilingual=true rich=true required=true}
        {/fbvFormSection}

        {fbvFormSection title="plugins.generic.customQuestions.questionDescription" for="description"}
            {fbvElement type="textarea" id="description" value=$description multilingual=true rich=true}
        {/fbvFormSection}

        {fbvFormSection for="required" list=true}
            {if $required}
                {assign var="checked" value=true}
            {else}
                {assign var="checked" value=false}
            {/if}
            {fbvElement
                type="checkbox"
                id="required"
                label="plugins.generic.customQuestions.required"
                checked=$checked
                inline="true"
            }
        {/fbvFormSection}

        {fbvFormSection for="questionType" list=true}
            {fbvElement
                type="select"
                label="plugins.generic.customQuestions.questionType"
                id="questionType"
                defaultLabel=""
                from=$customQuestionTypeOptions
                selected=$questionType
                size=$fbvStyles.size.MEDIUM
                required=true
            }
        {/fbvFormSection}

        <div id="questionOptions" class="full left">
            <div id="questionOptionsContainer" class="full left">
                {capture assign=questionOptionsUrl}
                    {url
                        router=\PKP\core\PKPApplication::ROUTE_COMPONENT
                        component="plugins.generic.customQuestions.controllers.listbuilder.CustomQuestionResponseItemListbuilderHandler"
                        op="fetch"
                        customQuestionId=$customQuestionId
                        escape=false
                    }
                {/capture}
                {load_url_in_div id="questionOptionsListbuilderContainer" url=$questionOptionsUrl}
            </div>
        </div>
        <p><span class="formRequired">{translate key="common.requiredField"}</span></p>

        {fbvFormButtons id="customQuestionFormSubmit" submitText="common.save"}
    {/fbvFormArea}
</form>
