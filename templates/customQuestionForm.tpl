<script>
    $(function() {ldelim}
        $('#customQuestionForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
    {rdelim});
</script>

<script type="text/javascript">
    function togglePossibleResponses(newValue, multipleResponsesElementTypesString) {ldelim}
        if (multipleResponsesElementTypesString.indexOf(';'+newValue+';') != -1) {ldelim}
            document.getElementById('customQuestionForm').addResponse.disabled = false;
        {rdelim} else {ldelim}
            if (document.getElementById('customQuestionForm').addResponse.disabled == false) {ldelim}
            alert({translate|json_encode key="manager.reviewFormElement.changeType"});
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

        {fbvFormSection title="manager.reviewFormElements.question" required=true for="question"}
            {fbvElement type="textarea" id="question" value=$question multilingual=true rich=true}
        {/fbvFormSection}

        {fbvFormSection title="manager.reviewFormElements.description" for="description"}
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
                label="manager.reviewFormElements.required"
                checked=$checked
                inline="true"
            }
        {/fbvFormSection}

        {fbvFormSection for="included" list=true}
            {if $included}
                {assign var="checked" value=true}
            {else}
                {assign var="checked" value=false}
            {/if}
            {fbvElement
                type="checkbox"
                id="included"
                label="manager.reviewFormElements.included"
                checked=$checked
                inline="true"
            }
        {/fbvFormSection}

        {fbvFormSection for="elementType" list=true}
            {fbvElement
                type="select"
                label="manager.reviewFormElements.elementType"
                id="elementType"
                defaultLabel=""
                from=$reviewFormElementTypeOptions
                selected=$elementType
                size=$fbvStyles.size.MEDIUM
                required=true
            }
        {/fbvFormSection}

        <div id="elementOptions" class="full left">
            <div id="elementOptionsContainer" class="full left">
                {capture assign=elementOptionsUrl}
                    {url
                        router=\PKP\core\PKPApplication::ROUTE_COMPONENT
                        component="listbuilder.settings.reviewForms.ReviewFormElementResponseItemListbuilderHandler"
                        op="fetch"
                        reviewFormId=$reviewFormId
                        reviewFormElementId=$reviewFormElementId
                        escape=false
                    }
                {/capture}
                {load_url_in_div id="elementOptionsListbuilderContainer" url=$elementOptionsUrl}
            </div>
        </div>
        <p><span class="formRequired">{translate key="common.requiredField"}</span></p>

        {fbvFormButtons id="customQuestionFormSubmit" submitText="common.save"}
    {/fbvFormArea}
</form>
