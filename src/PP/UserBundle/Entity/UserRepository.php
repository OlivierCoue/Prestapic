<?php

namespace PP\UserBundle\Entity;

/**
 * UserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends \Doctrine\ORM\EntityRepository
{
    public function getReportedUser(){
        $qb = $this->createQueryBuilder('u')                        
                        ->distinct(true)
                        ->leftJoin('u.profilImage', 'uP')
                        ->addSelect('uP')
                        ->where('u.enabled = true')
                        ->andWhere('u.reportNb > 0')
        ;
        
        return $qb
               ->getQuery()
               ->getResult()
            ;  
    }
    
    public function getActiveUsers($limit){
        $today = new \DateTime();         
        $lastWeek = new \DateTime();        
        $lastWeek->sub(new \DateInterval('P7D'));         
        
        $qb = $this->createQueryBuilder('u')                    
                    ->leftJoin('u.imageRequests', 'ir')
                    ->leftJoin('u.propositions', 'p')
                    ->where('u.enabled = true')
                    ->andWhere('ir.createdDate BETWEEN :lastWeek AND :today OR p.createdDate BETWEEN :lastWeek AND :today')
                    ->setParameter('lastWeek', $lastWeek)
                    ->setParameter('today', $today)
                    ->groupBy('u.id')
                    ->addSelect('COUNT(ir.id)+COUNT(p.id) as irNb')
                    ->addOrderBy('irNb', 'DESC')
                    ->setMaxResults($limit)                                        
        ; 
        return  $qb
                           ->getQuery()
                           ->getResult();        
    }
    
    public function getFollonwingIds($userId){
        $qb = $this->createQueryBuilder('u')
                    ->leftJoin('u.following', 'uF')
                    ->select('uF.id')
                    ->where('u.enabled = true AND u.id = :userId')                    
                    ->setParameter('userId', $userId);
        return $qb
                    ->getQuery()
                    ->getResult();
    }
    
    
    public function searchUser($userId, $search, $limit, $page){
        
        $qb = $this->createQueryBuilder('u');
        $qb = $qb   
                    ->distinct(true)
                    ->where($qb->expr()->like('u.name', ':search'))
                    ->setParameter('search', '%'.$search.'%')
                    ->andWhere("u.enabled = true")
                    ->leftJoin('u.messageThreads', 'mT')   
                    ->leftJoin('u.profilImage', 'pI')
                    ->addSelect('pI')
                    ->setMaxResults($limit);
        
        if($userId!=null){
            $qb = $qb->andWhere('u.id != :userId')
                    ->setParameter('userId', $userId);
        }
        
        $qb = $qb
                      ->setFirstResult(($page-1) * $limit)
                      ->setMaxResults($limit)
            ;
        
        try{
               return  $qb
                           ->getQuery()
                           ->getResult();
        }catch(\Doctrine\ORM\NoResultException $e){
               return null;
        }        
    }
             
    
    public function haveLikedRequest($userId, $imageRequestId){
        $qb = $this->createQueryBuilder('u')
                        ->select('u.id')
                        ->leftJoin('u.imageRequestsUpvoted', 'ir')
                        ->where('ir.id = :imageRequestId')
                        ->setParameter('imageRequestId', $imageRequestId)
                        ->andWhere('u.id = :userId')
                        ->setParameter('userId', $userId);                        
        
        try{
            $result = $qb
                    ->getQuery()
                    ->getSingleScalarResult();            
            return true;
            
        } catch (\Doctrine\ORM\NoResultException $ex) {            
            return false;
        }
       
    }
    
    public function haveLikedProposition($userId, $propositionId){
        $qb = $this->createQueryBuilder('u')
                        ->select('u.id')
                        ->leftJoin('u.propositionsUpvoted', 'p')
                        ->where('p.id = :propositionId')
                        ->setParameter('propositionId', $propositionId)
                        ->andWhere('u.id = :userId')
                        ->setParameter('userId', $userId);                        
        
        try{
            $result = $qb
                    ->getQuery()
                    ->getSingleScalarResult();            
            return true;
            
        } catch (\Doctrine\ORM\NoResultException $ex) {            
            return false;
        }       
    }       
    
    public function getUserBySlug($slug){
        $qb = $this->createQueryBuilder('u')
                        ->distinct(true)                       
                        ->where('u.slug = :slug')
                        ->setParameter('slug', $slug);
                        
        try{
            $result = $qb
                    ->getQuery()
                    ->getSingleResult();            
            return $result;
            
        } catch (\Doctrine\ORM\NoResultException $ex) {            
            return null;
        }
    }
    
    public function getInboxMessage($userId, $limit){
       $qb = $this->createQueryBuilder('u')
                       ->distinct(true)
                       ->leftJoin('u.messageThreads', 'mThread')
                       ->leftJoin('mThread.lastMessage', 'lm')
                       ->leftJoin('lm.author', 'lmA')
                       ->leftJoin('lm.target', 'lmT')
                       ->where('u.id = :userId')
                       ->setParameter('userId', $userId)                        
                       ->addSelect('mThread')
                       ->addSelect('lm')
                       ->addSelect('lmA')
                       ->addSelect('lmT')
                       ->orderBy('lm.createdDate', 'DESC');

       try{
           $result = $qb
                   ->getQuery()
                   ->getSingleResult();            
           return $result;

       } catch (\Doctrine\ORM\NoResultException $ex) {            
           return null;
       }
   }
       
}
