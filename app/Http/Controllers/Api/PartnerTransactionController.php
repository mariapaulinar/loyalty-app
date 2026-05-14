<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Card\TransactionService;
use App\Services\Staff\StaffService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class PartnerTransactionController extends Controller
{
    /**
     * Add a purchase transaction to a card where the authenticated partner has access to.
     *
     * @OA\Post(
     *     path="/{locale}/v1/partner/cards/{cardUID}/{memberUID}/transactions/purchases",
     *     operationId="addPurchase",
     *     tags={"Partner"},
     *     summary="Add a purchase to a card",
     *     description="Add a new purchase transaction to a card where the authenticated partner has access to.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(
     *         name="locale",
     *         in="path",
     *         description="Locale setting (e.g., `en-us`)",
     *         required=true,
     *
     *         @OA\Schema(type="string", default="en-us")
     *     ),
     *
     *     @OA\Parameter(
     *         name="cardUID",
     *         in="path",
     *         description="Unique identifier of the card to which the transaction will be added",
     *         required=true,
     *
     *         @OA\Schema(type="string", format="xxx-xxx-xxx-xxx", example="123-456-789-012")
     *     ),
     *
     *     @OA\Parameter(
     *         name="memberUID",
     *         in="path",
     *         description="Unique identifier for the member",
     *         required=true,
     *
     *         @OA\Schema(type="string", format="xxx-xxx-xxx-xxx", example="123-456-789-012")
     *     ),
     *
     *     @OA\RequestBody(
     *         description="Purchase data",
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="purchase_amount",
     *                 type="number",
     *                 description="The amount of money spent on the purchase",
     *                 example=100
     *             ),
     *             @OA\Property(
     *                 property="image",
     *                 type="string",
     *                 description="The image associated with the transaction",
     *                 example=""
     *             ),
     *             @OA\Property(
     *                 property="note",
     *                 type="string",
     *                 nullable=true,
     *                 description="An optional note for the purchase",
     *                 example=""
     *             ),
     *             @OA\Property(
     *                 property="staffId",
     *                 type="string",
     *                 description="Staff ID",
     *                 format="Kra8\Snowflake\HasSnowflakePrimary",
     *                 example="50510833641460081"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Purchase created successfully",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Transaction")
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized access",
     *
     *         @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Card not found",
     *
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     *
     * @param  string  $locale  Locale setting (e.g., 'en-us')
     * @param  string  $cardUID  Unique identifier of the card
     * @param  string  $memberUID  Unique identifier for the member
     * @param  Request  $request  The incoming HTTP request
     * @param  TransactionService  $transactionService  Service to handle transaction-related operations
     * @return Response JSON response containing transaction details or error message
     */
    public function addPurchase(
        string $locale,
        string $cardUID,
        string $memberUID,
        Request $request,
        TransactionService $transactionService,
        StaffService $staffService
    ): Response {
        // Authenticate the partner using 'partner_api' guard
        $partner = $request->user('partner_api');

        // Validate the purchase data
        try {
            $validatedData = $this->validatePurchaseData($request);
        } catch (ValidationException $e) {
            return $this->handleValidationException($e);
        }

        // Extract the image from the request
        $image = $request->file('image');

        $staff = $staffService->findActiveById($validatedData['staffId']);

        if (! $staff) {
            // Return an error response if the staff is not found
            return response()->json(['error' => 'Staff member not found'], 404);
        }

        // Create the new purchase
        $transaction = $transactionService->addPurchase(
            $memberUID,
            $cardUID,
            $staff,
            $validatedData['purchase_amount'],
            null,
            $image,
            $validatedData['note'],
            false
        );

        // Return the transaction details in a JSON response
        return response()->json($transaction);
    }

    /**
     * Add points to a card where the authenticated partner has access to.
     *
     * @OA\Post(
     *     path="/{locale}/v1/partner/cards/{cardUID}/{memberUID}/transactions/points",
     *     operationId="addPoints",
     *     tags={"Partner"},
     *     summary="Add points to a card",
     *     description="Add new points to a card where the authenticated partner has access to.",
     *     security={{"partner_auth_token": {}}},
     *
     *     @OA\Parameter(
     *         name="locale",
     *         in="path",
     *         description="Locale setting (e.g., `en-us`)",
     *         required=true,
     *
     *         @OA\Schema(type="string", default="en-us")
     *     ),
     *
     *     @OA\Parameter(
     *         name="cardUID",
     *         in="path",
     *         description="Unique identifier of the card to which the transaction will be added",
     *         required=true,
     *
     *         @OA\Schema(type="string", format="xxx-xxx-xxx-xxx", example="123-456-789-012")
     *     ),
     *
     *     @OA\Parameter(
     *         name="memberUID",
     *         in="path",
     *         description="Unique identifier for the member",
     *         required=true,
     *
     *         @OA\Schema(type="string", format="xxx-xxx-xxx-xxx", example="123-456-789-012")
     *     ),
     *
     *     @OA\RequestBody(
     *         description="Points data",
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="points",
     *                 type="number",
     *                 description="The number of points to add",
     *                 example=100
     *             ),
     *             @OA\Property(
     *                 property="image",
     *                 type="string",
     *                 description="The image associated with the transaction",
     *                 example=""
     *             ),
     *             @OA\Property(
     *                 property="note",
     *                 type="string",
     *                 nullable=true,
     *                 description="An optional note for the points addition",
     *                 example=""
     *             ),
     *             @OA\Property(
     *                 property="staffId",
     *                 type="string",
     *                 description="Staff ID",
     *                 format="Kra8\Snowflake\HasSnowflakePrimary",
     *                 example="50510833641460081"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Points added successfully",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Transaction")
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized access",
     *
     *         @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Card not found",
     *
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     *
     * @param  string  $locale  Locale setting (e.g., 'en-us')
     * @param  string  $cardUID  Unique identifier of the card
     * @param  string  $memberUID  Unique identifier for the member
     * @param  Request  $request  The incoming HTTP request
     * @param  TransactionService  $transactionService  Service to handle transaction-related operations
     * @return Response JSON response containing transaction details or error message
     */
    public function addPoints(
        string $locale,
        string $cardUID,
        string $memberUID,
        Request $request,
        TransactionService $transactionService,
        StaffService $staffService
    ): Response {
        // Authenticate the partner using 'partner_api' guard
        $partner = $request->user('partner_api');

        // Validate the points data
        try {
            $validatedData = $this->validatePointsData($request);
        } catch (ValidationException $e) {
            return $this->handleValidationException($e);
        }

        // Extract the image from the request
        $image = $request->file('image');

        $staff = $staffService->findActiveById($validatedData['staffId']);

        if (! $staff) {
            // Return an error response if the staff is not found
            return response()->json(['error' => 'Staff member not found'], 404);
        }

        // Add the new points
        $transaction = $transactionService->addPurchase(
            $memberUID,
            $cardUID,
            $staff,
            null,
            $validatedData['points'],
            $image,
            $validatedData['note'],
            true
        );

        // Return the transaction details in a JSON response
        return response()->json($transaction);
    }

    /**
     * Validate the purchase data.
     *
     * @throws ValidationException
     */
    private function validatePurchaseData(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'purchase_amount' => 'required|numeric|min:0',
            'note' => 'nullable|max:1024',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:51200',
            'staffId' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Validate the points data.
     *
     * @throws ValidationException
     */
    private function validatePointsData(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'points' => 'required|numeric|min:0',
            'note' => 'nullable|max:1024',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:51200',
            'staffId' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Handle the validation exception and return a proper JSON response.
     */
    private function handleValidationException(ValidationException $exception): Response
    {
        $errors = $exception->errors();

        return response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $errors,
        ], 422);
    }
}
