<?php

/**
 * This file is part of the FOSCommentBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PP\NotificationBundle\Controller;


use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\View\RouteRedirectView;
use FOS\RestBundle\View\View;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Templating\TemplateReference;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;

use PP\RequestBundle\Form\Type\ImageRequestType;
use PP\RequestBundle\Entity\ImageRequest;
use PP\PropositionBundle\Form\Type\PropositionType;
use PP\PropositionBundle\Entity\Proposition;
use Symfony\Component\Validator\Constraints\DateTime;
use PP\RequestBundle\Constant\Constants;
use PP\NotificationBundle\Constant\Notification;
use PP\NotificationBundle\Entity\NotificationFollow;
use PP\NotificationBundle\JsonNotificationModel\JsonNotificationFollow;
use PP\NotificationBundle\JsonNotificationModel\JsonNotificationNewProposition;
use PP\NotificationBundle\JsonNotificationModel\JsonNotificationSelected;
use PP\NotificationBundle\JsonNotificationModel\JsonNotification;

use PP\NotificationBundle\Constant\NotificationType;

class NotificationApiController extends Controller
{
    
    public function getThreadAction(){
        
        $response = new JsonResponse();
        $response->headers->set('Content-Type', 'application/json');
        
        if ($this->get('security.context')->isGranted('ROLE_USER')) {
            
            $currentUser = $this->getUser();
            $em = $this->getDoctrine()->getManager();                        
            
            if($currentUser != null){               
                
                $response->setData(json_encode(array('notifThreadSlug'=>$currentUser->getNotificationThread()->getSlug())));
                
            }else   $response->setStatusCode(Response::HTTP_FORBIDDEN);            
        
        }else $response->setStatusCode(Response::HTTP_FORBIDDEN);
        
        return $response;
    }
    
    
    public function getNotificationAction(Request $request, $page)
    {
        
        $response = new Response();
        $response->headers->set('Content-Type', 'application/x-javascript');
        
        if ($this->get('security.context')->isGranted('ROLE_USER')) {
                      
            $currentUser = $this->getUser();
            $em = $this->getDoctrine()->getManager();                        
            $notificationRepository = $em->getRepository('PPNotificationBundle:Notification');
            $notificationFolowRepository = $em->getRepository('PPNotificationBundle:NotificationFollow');
            $notificationNewPropositionRepository = $em->getRepository('PPNotificationBundle:NotificationNewProposition');
            $notificationSelectedRepository = $em->getRepository('PPNotificationBundle:NotificationSelected');                    
            $notificationMessageRepository = $em->getRepository('PPNotificationBundle:NotificationMessage');                    
                    
            if($currentUser != null){
                $data["notifThreadSlug"] = $currentUser->getNotificationThread()->getSlug();
                $data["showMoreApiUrl"] = $this->generateUrl('pp_notification_api_get_notification', array('page'=>$page+1), true);
                $data["notifications"] = array();
                $data["showMore"] = true;
 
                $notificationsList = $notificationRepository->getNotifications($currentUser->getId(), Notification::NOTIFICATION_PER_PAGE, $page);                
                
                if(sizeof($notificationsList) < Notification::NOTIFICATION_PER_PAGE)$data["showMore"] = false;
                
                if($notificationsList != null){
                    
                    foreach ($notificationsList as $notification){                                                                
                        
                        $setClickedUrl = $this->generateUrl('pp_notification_api_patch_clicked', array("id"=>$notification->getId()));                        
                        $messageThreadId = null;
                        
                        switch ($notification->getNotificationType()){
                            
                           
                            case NotificationType::FOLLOW:
                                $notificationFollow = $notificationFolowRepository->find($notification->getId());
                                $type = NotificationType::FOLLOW;
                                $redirectUrl = $this->generateUrl('pp_user_profile', array('slug' => $notificationFollow->getFollowYou()->getSlug())); 
                                $authorName =  $notificationFollow->getFollowYou()->getName();
                                $authorId =  $notificationFollow->getFollowYou()->getId();
                                $authorImg = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() .'/'. $notificationFollow->getFollowYou()->getProfilImage()->getWebPath("70x70");
                                $targetTitle = null;;                                                                                                
                                break;                           
                            
                            case NotificationType::NEW_PROPOSITION:
                                $notificationNewProposition = $notificationNewPropositionRepository->find($notification->getId());                                
                                $type = NotificationType::NEW_PROPOSITION;                                                                        
                                $redirectUrl = $this->generateUrl('pp_request_view', array('slug' => $notificationNewProposition->getProposition()->getImageRequest()->getSlug()));                                    
                                $authorName = $notificationNewProposition->getProposition()->getAuthor()->getName();
                                $authorId =  $notificationNewProposition->getProposition()->getAuthor()->getId();
                                $authorImg = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() .'/'. $notificationNewProposition->getProposition()->getAuthor()->getProfilImage()->getWebPath("70x70");
                                $targetTitle = $notificationNewProposition->getProposition()->getImageRequest()->getTitle();
                                break;
                            
                            case NotificationType::PROPOSITION_SELECTED:
                                $notificationSelected = $notificationSelectedRepository->find($notification->getId());                                
                                $type = NotificationType::PROPOSITION_SELECTED;
                                $redirectUrl = $this->generateUrl('pp_request_view', array('slug' => $notificationSelected->getImageRequest()->getSlug()));
                                $authorName = $notificationSelected->getImageRequest()->getAuthor()->getName();
                                $authorId =  $notificationSelected->getImageRequest()->getAuthor()->getId();
                                $authorImg = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() .'/'. $notificationSelected->getImageRequest()->getAuthor()->getProfilImage()->getWebPath("70x70");
                                $targetTitle = $notificationSelected->getImageRequest()->getTitle();                                                                 
                                break;
                            
                            case NotificationType::MESSAGE:
                                $notificationMessage = $notificationMessageRepository->find($notification->getId());                                
                                $type = NotificationType::MESSAGE;
                                $redirectUrl = $this->generateUrl('pp_user_profile', array('slug' => $notificationMessage->getAuthor()->getSlug()));
                                $authorName = $notificationMessage->getAuthor()->getName();
                                $authorId =  $notificationMessage->getAuthor()->getId();
                                $authorImg = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() .'/'. $notificationMessage->getAuthor()->getProfilImage()->getWebPath("70x70");
                                $targetTitle = null;
                                $messageThreadId = $notificationMessage->getMessage()->getMessageThread()->getId();
                                break;
                        }
                        
                        $jsonNotication = new JsonNotification(
                                    $type,
                                    $notification->getIsViewed(),
                                    $notification->getIsClicked(),
                                    $notification->getCreateDate(),
                                    $this->container->get('pp_notification.ago')->ago($notification->getCreateDate()),
                                    $redirectUrl,
                                    $setClickedUrl,
                                    $authorId,
                                    $authorName,
                                    $authorImg,
                                    $targetTitle,
                                    $messageThreadId
                        );
                        array_push($data["notifications"], $jsonNotication );
                    }
                }

                /*usort($data["notifications"], function($a, $b){
                    return strtotime($b->date->format('Y-m-d H:i:s')) - strtotime($a->date->format('Y-m-d H:i:s'));
                });*/                                                
                echo json_encode($data);                
                $response->setStatusCode(Response::HTTP_OK);                
                return $response;
                
            }else   $response->setStatusCode(Response::HTTP_FORBIDDEN);            
        
        }else $response->setStatusCode(Response::HTTP_FORBIDDEN);
        
        return $response;
        
    }
    
    public function patchViewedAction(){
        
        $response = new JsonResponse();
        $response->headers->set('Content-Type', 'application/json');
        
        if ($this->get('security.context')->isGranted('ROLE_USER')) {
            
            $currentUser = $this->getUser();
            $em = $this->getDoctrine()->getManager();                        
            $notificationRepository = $em->getRepository('PPNotificationBundle:Notification');
            
            if($currentUser != null){               
                
                $notificationsNotViewList = $notificationRepository->getNotificationsNotViewed($currentUser->getId());
                
                if($notificationsNotViewList != null){
                    
                    foreach ($notificationsNotViewList as $notification){
                        $notification->setIsViewed(true);
                         $currentUser->decrementNotificationsNb();
                        $em->persist($notification);                        
                    }
                                                           
                    $em->persist($currentUser);
                    $em->flush();
                }
                
                 $response->setStatusCode(Response::HTTP_OK);
                
            }else  $response->setStatusCode(Response::HTTP_FORBIDDEN);            
        
        }else $response->setStatusCode(Response::HTTP_FORBIDDEN);
        
        return $response;
        
    }
    
    public function patchClickedAction($id){
        
        $response = new JsonResponse();
        $response->headers->set('Content-Type', 'application/json');
        
        if ($this->get('security.context')->isGranted('ROLE_USER')) {
            
            $currentUser = $this->getUser();
            $em = $this->getDoctrine()->getManager();                        
            $notificationRepository = $em->getRepository('PPNotificationBundle:Notification');
            
            if($currentUser != null){               
                
                $notification = $notificationRepository->find($id);
                
                if($notification != null){
                    
                    $notification->setIsClicked(true);                                                           
                    $em->persist($notification);
                    $em->flush();
                    
                }else  $response->setStatusCode(Response::HTTP_NO_CONTENT);
                
                $response->setStatusCode(Response::HTTP_OK);
                
            }else  $response->setStatusCode(Response::HTTP_FORBIDDEN);            
        
        }else $response->setStatusCode(Response::HTTP_FORBIDDEN);
        
        return $response;        
    }
    
    
    private function getViewHandler()
    {
        return $this->container->get('fos_rest.view_handler');
    }
}
