<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/


namespace CustomDelivery\Controller;

use CustomDelivery\CustomDelivery;
use CustomDelivery\Model\CustomDeliverySlice;
use CustomDelivery\Model\CustomDeliverySliceQuery;
use Propel\Runtime\Map\TableMap;
use Symfony\Component\HttpFoundation\Response;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Template\ParserContext;
use Thelia\Core\Translation\Translator;
use Thelia\Model\ConfigQuery;
use Thelia\Tools\URL;

/**
 * Class BackController
 * @package CustomDelivery\Controller
 * @author Julien Chanséaume <julien@thelia.net>
 */
class BackController extends BaseAdminController
{
    protected $currentRouter = 'router.customdelivery';

    protected $useFallbackTemplate = true;

    /**
     * Save slice
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function saveAction(Request $request)
    {
        $response = $this->checkAuth([], ['customdelivery'], AccessManager::UPDATE);

        if (null !== $response) {
            return $response;
        }

        $this->checkXmlHttpRequest();

        $responseData = [
            "success" => false,
            "message" => '',
            "slice" => null
        ];

        $messages = [];
        $response = null;
        $config = CustomDelivery::getConfig();

        try {
            if (0 !== $id = (int)$request->get('id', 0)) {
                $slice = CustomDeliverySliceQuery::create()->findPk($id);
            } else {
                $slice = new CustomDeliverySlice();
            }

            if (0 !== $areaId = (int)$request->get('area', 0)) {
                $slice->setAreaId($areaId);
            } else {
                $messages[] = Translator::getInstance()->trans(
                    'The area is not valid',
                    [],
                    CustomDelivery::MESSAGE_DOMAIN
                );
            }

            if ($config['method'] !== CustomDelivery::METHOD_WEIGHT) {
                $priceMax = $this->getFloatVal($request->get('priceMax', 0));
                if (0 < $priceMax) {
                    $slice->setPriceMax($priceMax);
                } else {
                    $messages[] = Translator::getInstance()->trans(
                        'The price max value is not valid',
                        [],
                        CustomDelivery::MESSAGE_DOMAIN
                    );
                }
            }

            if ($config['method'] !== CustomDelivery::METHOD_PRICE) {
                $weightMax = $this->getFloatVal($request->get('weightMax', 0));
                if (0 < $weightMax) {
                    $slice->setWeightMax($weightMax);
                } else {
                    $messages[] = Translator::getInstance()->trans(
                        'The weight max value is not valid',
                        [],
                        CustomDelivery::MESSAGE_DOMAIN
                    );
                }
            }

            $price = $this->getFloatVal($request->get('price', 0));
            if (0 <= $price) {
                $slice->setPrice($price);
            } else {
                $messages[] = Translator::getInstance()->trans(
                    'The price value is not valid',
                    [],
                    CustomDelivery::MESSAGE_DOMAIN
                );
            }

            if (0 === count($messages)) {
                $slice->save();
                $messages[] = Translator::getInstance()->trans(
                    'Your slice has been saved',
                    [],
                    CustomDelivery::MESSAGE_DOMAIN
                );

                $responseData['success'] = true;
                $responseData['slice'] = $slice->toArray(TableMap::TYPE_STUDLYPHPNAME);
            }
        } catch (\Exception $e) {
            $message[] = $e->getMessage();
        }

        $responseData['message'] = $messages;

        return $this->jsonResponse(json_encode($responseData));
    }

    protected function getFloatVal($val, $default=-1)
    {
        if (preg_match("#^([\d.,]+)$#", $val, $match)) {
            return (float) str_replace(array('.', ','), array('', '.'), $match[0]);
        }

        return $default;
    }

    /**
     * Save slice
     *
     * @return Response
     */
    public function deleteAction(Request $request)
    {
        $response = $this->checkAuth([], ['customdelivery'], AccessManager::DELETE);

        if (null !== $response) {
            return $response;
        }

        $this->checkXmlHttpRequest();

        $responseData = [
            "success" => false,
            "message" => '',
            "slice" => null
        ];

        $response = null;

        try {
            if (0 !== $id = (int)$request->get('id', 0)) {
                $slice = CustomDeliverySliceQuery::create()->findPk($id);
                $slice->delete();
                $responseData['success'] = true;
            } else {
                $responseData['message'] = Translator::getInstance()->trans(
                    'The slice has not been deleted',
                    [],
                    CustomDelivery::MESSAGE_DOMAIN
                );
            }
        } catch (\Exception $e) {
            $responseData['message'] = $e->getMessage();
        }

        return $this->jsonResponse(json_encode($responseData));
    }

    /**
     * Save module configuration
     *
     * @param ParserContext $parserContext
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function saveConfigurationAction()
    {
        $response = $this->checkAuth([AdminResources::MODULE], ['customdelivery'], AccessManager::UPDATE);

        if (null !== $response) {
            return $response;
        }

        $form = $this->createForm('customdelivery.configuration.form');
        $message = "";

        $response = null;

        try {
            $vform = $this->validateForm($form);
            $data = $vform->getData();

            ConfigQuery::write(
                CustomDelivery::CONFIG_TRACKING_URL,
                $data['url']
            );
            ConfigQuery::write(
                CustomDelivery::CONFIG_PICKING_METHOD,
                $data['method']
            );
            ConfigQuery::write(
                CustomDelivery::CONFIG_TAX_RULE_ID,
                $data['tax']
            );
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }
        if ($message) {
            $form->setErrorMessage($message);
            $this->getParserContext()
                ->addForm($form)
                ->setGeneralError($message);

            return $this->render(
                "module-configure",
                ["module_code" => CustomDelivery::getModuleCode()]
            );
        }

        return $this->generateRedirect(
            URL::getInstance()->absoluteUrl("/admin/module/" . CustomDelivery::getModuleCode())
        );
    }
}
