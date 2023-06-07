<?php

namespace APP\plugins\generic\customQuestions\api\v1\customQuestionResponses;

use PKP\handler\APIHandler;
use PKP\security\Role;
use Slim\Http\Request;
use PKP\core\APIResponse;
use PKP\security\authorization\ContextAccessPolicy;
use PKP\security\authorization\UserRolesRequiredPolicy;

class CustomQuestionResponseHandler extends APIHandler
{
    public function __construct()
    {
        $this->_handlerPath = 'customQuestionResponses';
        $this->_endpoints = [
            'GET' => [
                [
                    'pattern' => $this->getEndpointPattern(),
                    'handler' => [$this, 'get'],
                    'roles' => [
                        Role::ROLE_ID_MANAGER,
                        Role::ROLE_ID_SUB_EDITOR,
                        Role::ROLE_ID_ASSISTANT,
                        Role::ROLE_ID_AUTHOR
                    ],
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

    public function get(Request $slimRequest, APIResponse $response, array $args): APIResponse
    {
        return $response->withJson(['message' => 'ok'], 200);
    }
}
