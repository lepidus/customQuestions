<?php

namespace APP\plugins\generic\customQuestions\api\v1\customQuestionResponses;

use APP\plugins\generic\customQuestions\classes\facades\Repo;
use PKP\core\APIResponse;
use PKP\handler\APIHandler;
use PKP\security\authorization\ContextAccessPolicy;
use PKP\security\authorization\UserRolesRequiredPolicy;
use PKP\security\Role;
use Slim\Http\Request;

class CustomQuestionResponseHandler extends APIHandler
{
    public function __construct()
    {
        $roles = [
            Role::ROLE_ID_MANAGER,
            Role::ROLE_ID_SUB_EDITOR,
            Role::ROLE_ID_ASSISTANT,
            Role::ROLE_ID_AUTHOR
        ];

        $this->_handlerPath = 'customQuestionResponses';
        $this->_endpoints = [
            'PUT' => [
                [
                    'pattern' => $this->getEndpointPattern() . '/{submissionId:\d+}',
                    'handler' => [$this, 'edit'],
                    'roles' => $roles,
                ],
            ],
        ];

        parent::__construct();
    }

    public function authorize($request, &$args, $roleAssignments)
    {
        $this->addPolicy(new UserRolesRequiredPolicy($request), true);

        $this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));

        return parent::authorize($request, $args, $roleAssignments);
    }

    public function edit(Request $slimRequest, APIResponse $response, array $args): APIResponse
    {
        $request = $this->getRequest();
        $context = $request->getContext();
        $params = $slimRequest->getParsedBody();
        $submissionId = $args['submissionId'];

        foreach ($slimRequest->getParsedBody() as $fieldName => $value) {
            $fieldNameSplitted = preg_split('/-/', $fieldName);
            $customQuestionId = end($fieldNameSplitted);
            $customQuestion = Repo::customQuestion()->get($customQuestionId, $context->getId());

            $customQuestionResponse = Repo::customQuestionResponse()
                ->getByCustomQuestionId($customQuestionId, $submissionId);

            if (is_null($customQuestionResponse)) {
                $customQuestionResponse = Repo::customQuestionResponse()->newDataObject([
                    'submissionId' => $submissionId,
                    'customQuestionId' => $customQuestionId,
                ]);
                Repo::customQuestionResponse()->add($customQuestionResponse);
            }

            Repo::customQuestionResponse()->edit($customQuestionResponse, [
                'value' => $value,
                'responseType' => $customQuestion->getCustomQuestionResponseType(),
            ]);
        }

        $customQuestionResponses = Repo::customQuestionResponse()->getCollector()
            ->filterBySubmissionIds([$submissionId])
            ->getMany();

        $customQuestionResponsesProps = [];
        foreach ($customQuestionResponses as $customQuestionResponse) {
            $customQuestionResponsesProps[] = $customQuestionResponse->getAllData();
        }

        return $response->withJson($customQuestionResponsesProps, 200);
    }
}
