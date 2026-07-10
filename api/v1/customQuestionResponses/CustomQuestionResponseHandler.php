<?php

namespace APP\plugins\generic\customQuestions\api\v1\customQuestionResponses;

use APP\core\Application;
use APP\plugins\generic\customQuestions\classes\components\forms\CustomQuestionsFormProvider;
use APP\plugins\generic\customQuestions\classes\facades\Repo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use PKP\core\PKPBaseController;
use PKP\core\PKPRequest;
use PKP\security\authorization\ContextAccessPolicy;
use PKP\security\authorization\SubmissionAccessPolicy;
use PKP\security\Role;

class CustomQuestionResponseHandler extends PKPBaseController
{
    public function getHandlerPath(): string
    {
        return 'customQuestionResponses';
    }

    public function getRouteGroupMiddleware(): array
    {
        return [
            'has.user',
            'has.context',
        ];
    }

    public function getGroupRoutes(): void
    {
        Route::middleware([
            self::roleAuthorizer([
                Role::ROLE_ID_MANAGER,
                Role::ROLE_ID_SUB_EDITOR,
                Role::ROLE_ID_ASSISTANT,
                Role::ROLE_ID_AUTHOR,
            ]),
        ])->group(function () {
            Route::get('{submissionId}', $this->get(...))
                ->name('customQuestionResponses.get')
                ->whereNumber('submissionId');

            Route::put('{submissionId}', $this->edit(...))
                ->name('customQuestionResponses.edit')
                ->whereNumber('submissionId');
        });
    }

    public function authorize(PKPRequest $request, array &$args, array $roleAssignments): bool
    {
        $this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
        $this->addPolicy(new SubmissionAccessPolicy($request, $args, $roleAssignments));

        return parent::authorize($request, $args, $roleAssignments);
    }

    public function get(Request $illuminateRequest): JsonResponse
    {
        $request = $this->getRequest();
        $submission = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_SUBMISSION);
        $config = (new CustomQuestionsFormProvider())->getConfig(
            $request,
            $submission,
            $request->getContext()
        );

        return response()->json($config, Response::HTTP_OK);
    }

    public function edit(Request $illuminateRequest): JsonResponse
    {
        $request = $this->getRequest();
        $context = $request->getContext();
        $submission = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_SUBMISSION);
        $submissionId = $submission->getId();

        foreach ($illuminateRequest->input() as $fieldName => $value) {
            $fieldNameParts = explode('-', $fieldName);
            $customQuestionId = (int) array_pop($fieldNameParts);

            $customQuestion = Repo::customQuestion()->get($customQuestionId, $context->getId());
            if (is_null($customQuestion)) {
                continue;
            }

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

        return response()->json($customQuestionResponsesProps, Response::HTTP_OK);
    }
}
