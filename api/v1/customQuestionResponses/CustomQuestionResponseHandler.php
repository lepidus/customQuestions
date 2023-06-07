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
        $roles = [
            Role::ROLE_ID_MANAGER,
            Role::ROLE_ID_SUB_EDITOR,
            Role::ROLE_ID_ASSISTANT,
            Role::ROLE_ID_AUTHOR
        ];

        $this->_handlerPath = 'customQuestionResponses';
        $this->_endpoints = [
            'GET' => [
                [
                    'pattern' => $this->getEndpointPattern(),
                    'handler' => [$this, 'get'],
                    'roles' => $roles,
                ],
            ],
            'PUT' => [
                [
                    'pattern' => $this->getEndpointPattern(),
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

    public function get(Request $slimRequest, APIResponse $response, array $args): APIResponse
    {
        return $response->withJson(['message' => 'ok'], 200);
    }

    public function edit(Request $slimRequest, APIResponse $response, array $args): APIResponse
    {
        return $response->withJson(['message' => 'ok'], 200);
    }
}
