<?php

namespace PP\CommentBundle\Entity;

/**
 * CommentRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CommentRepository extends \Doctrine\ORM\EntityRepository
{
    public function getComments($id, $limit, $page){
        $qb = $this->createQueryBuilder('c')                    
                        ->distinct(true)
                        ->leftJoin('c.commentThread', 'ct')
                        ->addSelect('c')
                        ->leftJoin('c.author', 'cA')
                        ->addSelect('cA')                        
                        ->where('ct.id = :id')
                        ->setParameter('id', $id)
                        ->orderBy('c.createdDate', 'DESC')                                
        ;
        
        $qb = $qb
                   ->setFirstResult(($page-1) * $limit)
                   ->setMaxResults($limit)
               ;
        return $qb
               ->getQuery()
               ->getResult()
            ;  
    }
    
}
