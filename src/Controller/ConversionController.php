<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\CurrencyRepository;
use App\Repository\RateRepository;
use App\Validation\ConversionRequest;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

class ConversionController extends AbstractController
{
    /**
     * @var RateRepository
     */
    private RateRepository $rateRepository;

    /**
     * @var CurrencyRepository
     */
    private CurrencyRepository $currencyRepository;

    /**
     * @var ValidatorInterface
     */
    private ValidatorInterface $validator;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * ConversionController constructor.
     * @param CurrencyRepository $currencyRepository
     * @param RateRepository $rateRepository
     * @param ValidatorInterface $validator
     * @param LoggerInterface $logger
     */
    public function __construct(
        CurrencyRepository $currencyRepository,
        RateRepository $rateRepository,
        ValidatorInterface $validator,
        LoggerInterface $logger
    ) {
        $this->currencyRepository = $currencyRepository;
        $this->rateRepository = $rateRepository;
        $this->logger = $logger;
        $this->validator = $validator;
    }


    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $currencies = $this->currencyRepository->findAll();

        return $this->render('conversion/index.html.twig', [
            'currencies' => $currencies,
        ]);
    }

    #[Route('/api/conversion', name: 'conversion', methods: ['POST'])]
    public function conversion(Request $request): JsonResponse
    {
        try {
            $conversionRequest = new ConversionRequest($request->toArray());
            $errors = $this->validator->validate($conversionRequest);

            if (count($errors) > 0) {
                return new JsonResponse([
                    'success' => false,
                    'error_message' => (string) $errors,
                ]);
            }

            if ($conversionRequest->getCurrencyFromId() == $conversionRequest->getCurrencyToId()) {
                return new JsonResponse([
                    'success' => true,
                    'result' => $conversionRequest->getAmount(),
                ]);
            }

            $rate = $this->rateRepository->findDirectRate(
                $conversionRequest->getCurrencyFromId(),
                $conversionRequest->getCurrencyToId()
            );

            if ($rate == null && $this->getParameter('app.conversion_through_third_currency_allowed') == 'true') {
                $rate = $this->rateRepository->findRateThroughThirdCurrency(
                    $conversionRequest->getCurrencyFromId(),
                    $conversionRequest->getCurrencyToId()
                );
            }

            if ($rate == null) {
                return  new JsonResponse([
                    'success' => false,
                    'error_message' => 'The exchange rate between the requested currencies was not found.'
                ]);
            }

            return new JsonResponse([
                'success' => true,
                'result' => $conversionRequest->getAmount() * $rate
            ]);
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                "Error during currencies conversion. Error: %s, Trace: %s.",
                $e->getMessage(),
                $e->getTraceAsString(),
            ));
            return new JsonResponse([
                'success' => false,
                'error_message' => 'Something went wrong.',
            ]);
        }
    }
}
