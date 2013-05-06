<?php

namespace Club\UserBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * UserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends EntityRepository
{
  public function findNextMemberNumber()
  {
    $i = 0;
    $users = $this->_em->getRepository('ClubUserBundle:User')->findBy(array(),array('member_number' => 'asc'));
    if (!$users) return 1;

    foreach ($users as $user) {
      $i++;
      if ($user->getMemberNumber() != $i)

        return $i;
    }

    $i++;

    return $i;
  }

  public function getUsersListWithPagination($filter, $order_by = array(), $offset = 0, $limit = 0)
  {
    //Create query builder for languages table
    $qb = $this->getQueryBuilderByFilter($filter);

    //Show all if offset and limit not set, also show all when limit is 0
    if ((isset($offset)) && (isset($limit))) {
      if ($limit > 0) {
        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);
      }
      //else we want to display all items on one page
    }

    //Adding defined sorting parameters from variable into query
    foreach ($order_by as $key => $value) {
      switch ($key) {
      case 'id':
      case 'member_number':
      case 'created_at':
        $qb->add('orderBy', 'u.' . $key . ' ' . $value);
        break;
      case 'first_name':
        $qb->add('orderBy', 'p.' . $key . ' ' . $value);
        break;
      }
    }
    //Get our query
    $q = $qb->getQuery();
    //Return result
    return $q->getResult();
  }

  public function getUsersCount($filter)
  {
    return count($this->getUsers($filter));
  }

  public function getUsers($filter)
  {
    $qb = $this->getQueryBuilderByFilter($filter);

    return $qb->getQuery()->getResult();
  }

  protected function getQueryBuilderByFilter(\Club\UserBundle\Entity\Filter $filter)
  {
    $qb = $this->getQueryBuilder();

    foreach ($filter->getAttributes() as $attr) {
      if ($attr->getValue() != '') {
        switch ($attr->getAttribute()) {
        case 'name':
          $qb = $this->filterName($qb,$attr->getValue());
          break;
        case 'phone':
          $qb = $this->filterPhone($qb,$attr->getValue());
          break;
        case 'email_address':
          $qb = $this->filterEmailAddress($qb,$attr->getValue());
          break;
        case 'member_number':
          $qb = $this->filterMemberNumber($qb,$attr->getValue());
          break;
        case 'min_age':
          $qb = $this->filterMinAge($qb,$attr->getValue());
          break;
        case 'max_age':
          $qb = $this->filterMaxAge($qb,$attr->getValue());
          break;
        case 'gender':
          $qb = $this->filterGender($qb,$attr->getValue());
          break;
        case 'postal_code':
          $qb = $this->filterPostalCode($qb,$attr->getValue());
          break;
        case 'city':
          $qb = $this->filterCity($qb,$attr->getValue());
          break;
        case 'country':
          $qb = $this->filterCountry($qb,$attr->getValue());
          break;
        case 'active':
          $qb = $this->filterActive($qb,$attr->getValue());
          break;
        case 'has_ticket':
          $qb = $this->filterHasTicket($qb,$attr->getValue());
          break;
        case 'has_subscription':
          $qb = $this->filterHasSubscription($qb,$attr->getValue());
          break;
        case 'subscription_start':
          $qb = $this->filterSubscriptionStart($qb,$attr->getValue());
          break;
        case 'location':
          $qb = $this->filterLocation($qb,explode(",", $attr->getValue()));
          break;
        }
      }
    }

    return $qb;
  }

  protected function getQueryByFilter(\Club\UserBundle\Entity\Filter $filter)
  {
    return $this->getQueryBuilderByFilter($filter)->getQuery();
  }

  public function getByGroup(\Club\UserBundle\Entity\Group $group)
  {
    return $this->getQueryByGroup($group)->getResult();
  }

  public function getQueryByGroup(\Club\UserBundle\Entity\Group $group)
  {
    $qb = $this->getQueryBuilder();

    if ($group->getGender() != '') {
      $qb = $this->filterGender($qb,$group->getGender());
    }

    if ($group->getMinAge() != '') {
      $qb = $this->filterMinAge($qb,$group->getMinAge());
    }

    if ($group->getMaxAge() != '') {
      $qb = $this->filterMaxAge($qb,$group->getMaxAge());
    }

    if ($group->getActiveMember() != '') {
      $qb = $this->filterActive($qb,$group->getActiveMember());
    }

    if (count($group->getLocation()) > 0) {
      $location_arr = array();
      foreach ($group->getLocation() as $location) {
        $location_arr[] = $location->getId();
      }
      $qb = $this->filterLocation($qb,$location_arr);
    }

    return $qb->getQuery();
  }

  protected function getQueryBuilder()
  {
    $this->has_joined_addr = false;
    $this->has_joined_phone = false;
    $this->has_joined_email = false;
    $this->has_joined_sub = false;

    return $this->createQueryBuilder('u')
      ->select('u, p')
      ->where('u.status = :status')
      ->leftJoin('u.profile','p')
      ->setParameter('status', \Club\UserBundle\Entity\User::ACTIVE)
      ;
  }

  protected function filterName($qb,$value)
  {
    $qb->andWhere("CONCAT(CONCAT(p.first_name, ' '), p.last_name) LIKE :name");
    $qb->setParameter('name', '%'.$value.'%');

    return $qb;
  }

  protected function filterMemberNumber($qb,$value)
  {
    $qb->andWhere(
      $qb->expr()->eq('u.member_number',':number')
    );
    $qb->setParameter('number', $value);

    return $qb;
  }

  protected function filterMinAge($qb,$value)
  {
    $qb->andWhere(
      $qb->expr()->lte('p.day_of_birth',':min_age')
    );
    $qb->setParameter('min_age', date('Y-m-d',mktime(0,0,0,date('n'),date('j'),date('Y')-$value)));

    return $qb;
  }

  protected function filterMaxAge($qb,$value)
  {
    $qb->andWhere(
      $qb->expr()->gte('p.day_of_birth',':max_age')
    );
    $qb->setParameter('max_age', date('Y-m-d',mktime(0,0,0,date('n'),date('j'),date('Y')-$value)));

    return $qb;
  }

  protected function filterGender($qb,$value)
  {
    $qb->andWhere(
      $qb->expr()->eq('p.gender',':gender')
    );
    $qb->setParameter('gender', $value);

    return $qb;
  }

  protected function filterPhone($qb,$value)
  {
    if (!$this->has_joined_phone) {
      $qb->join('p.profile_phone','pp');
      $this->has_joined_phone = true;
    }

    $qb->andWhere(
      $qb->expr()->eq('pp.phone_number',':phone')
    );
    $qb->setParameter('phone', $value);

    return $qb;
  }

  protected function filterEmailAddress($qb,$value)
  {
    if (!$this->has_joined_email) {
      $qb->join('p.profile_email','pe');
      $this->has_joined_email = true;
    }

    $qb->andWhere(
      $qb->expr()->eq('pe.email_address',':email')
    );
    $qb->setParameter('email', $value);

    return $qb;
  }

  protected function filterPostalCode($qb,$value)
  {
    if (!$this->has_joined_addr) {
      $qb->join('p.profile_address','pa');
      $this->has_joined_addr = true;
    }

    $qb->andWhere(
      $qb->expr()->eq('pa.postal_code',':postal_code')
    );
    $qb->setParameter('postal_code', $value);

    return $qb;
  }

  protected function filterCity($qb,$value)
  {
    if (!$this->has_joined_addr) {
      $qb->join('p.profile_address','pa');
      $this->has_joined_addr = true;
    }

    $qb->andWhere(
      $qb->expr()->eq('pa.city',':city')
    );
    $qb->setParameter('city', $value);

    return $qb;
  }

  protected function filterCountry($qb,$value)
  {
    if (!$this->has_joined_addr) {
      $qb->join('p.profile_address','pa');
      $this->has_joined_addr = true;
    }

    $qb->andWhere(
      $qb->expr()->eq('pa.country',':country')
    );
    $qb->setParameter('country', $value);

    return $qb;
  }

  protected function filterActive($qb,$value)
  {
    if (!$this->has_joined_sub) {
      $qb->leftJoin('u.subscriptions','s');
      $this->has_joined_sub = true;
    }

    if ($value) {
      $qb->andWhere('((s.start_date <= :date AND s.expire_date >= :date) OR (s.start_date IS NOT NULL AND s.expire_date IS NULL))');
    }
    $qb->setParameter('date',date('Y-m-d H:i:s'));

    return $qb;
  }

  protected function filterHasTicket($qb,$value)
  {
    if (!$this->has_joined_sub) {
      $qb->leftJoin('u.subscriptions','s');
      $this->has_joined_sub = true;
    }

    if ($value) {
      $qb->andWhere('s.type = :type');
    } else {
      $qb->andWhere('s.type <> :type');
    }
    $qb->setParameter('type','ticket');

    return $qb;
  }

  protected function filterHasSubscription($qb,$value)
  {
    if (!$this->has_joined_sub) {
      $qb->leftJoin('u.subscriptions','s');
      $this->has_joined_sub = true;
    }

    if ($value) {
      $qb->andWhere('s.type = :type');
    } else {
      $qb->andWhere('s.type <> :type');
    }
    $qb->setParameter('type','subscription');

    return $qb;
  }

  protected function filterSubscriptionStart($qb,$value)
  {
    if ($value) {
      $date = unserialize($value);
      if (!$this->has_joined_sub) {
        $qb->leftJoin('u.subscriptions','s');
        $this->has_joined_sub = true;
      }

      $start = new \DateTime($date->format('Y-m-d 00:00:00'));
      $end = new \DateTime($date->format('Y-m-d 23:59:59'));

      $qb->andWhere(
        '(s.start_date <= :start_date and s.expire_date >= :end_date) OR '.
        '(s.start_date <= :start_date and s.expire_date <= :end_date and s.expire_date >= :start_date) OR '.
        '(s.start_date >= :start_date and s.expire_date >= :end_date and s.start_date < :end_date) OR '.
        '(s.start_date >= :start_date and s.expire_date <= :end_date and s.expire_date >= :start_date)'
      );
      $qb->setParameter('start_date',$start);
      $qb->setParameter('end_date',$end);
    }

    return $qb;
  }

  protected function filterLocation($qb,array $value)
  {
    $locations = array();
    foreach ($value as $id) {
      // FIXME, has to be infinitive loop
      $location = $this->_em->find('ClubUserBundle:Location',$id);
      $locations[] = $location->getId();

      if ($location->getLocation()) {
        $locations[] = $location->getLocation()->getId();

        if ($location->getLocation()->getLocation()) {
          $locations[] = $location->getLocation()->getLocation()->getId();

          if ($location->getLocation()->getLocation()->getLocation()) {
            $locations[] = $location->getLocation()->getLocation()->getLocation()->getId();
          }
        }
      }
    }

    $str = "";
    foreach ($locations as $id) {
      $str .= " sl.id = $id OR ";
    }
    $str = preg_replace("/OR $/","",$str);

    if (!$this->has_joined_sub) {
      $qb->leftJoin('u.subscriptions','s');
      $this->has_joined_sub = true;
    }

    $qb
      ->leftJoin('s.location','sl')
      ->andWhere('('.$str.')');

    return $qb;
  }

  public function getGroupsByUser(\Club\UserBundle\Entity\User $user)
  {
    $location_str = '';
    $used = array();

    foreach ($user->getSubscriptions() as $subscription) {
      foreach ($subscription->getLocation() as $location) {

        if (!isset($used[$location->getId()])) {
          $location_str .= 'l.id = '.$location->getId().' OR ';
          $used[$location->getId()] = 1;
        }

        if ($location->getLocation()) {
          if (!isset($used[$location->getLocation()->getId()])) {
            $location_str .= 'l.id = '.$location->getLocation()->getId().' OR ';
            $used[$location->getLocation()->getId()] = 1;
          }

          if ($location->getLocation()->getLocation()) {

            if (!isset($used[$location->getLocation()->getLocation()->getId()])) {
              $location_str .= 'l.id = '.$location->getLocation()->getLocation()->getId().' OR ';
              $used[$location->getLocation()->getLocation()->getId()] = 1;
            }
          }
        }
      }
    }

    $subs = $this->_em->getRepository('ClubShopBundle:Subscription')->getActiveSubscriptions($user);
    $active = !$subs ? false : true;

    return $this->_em->createQueryBuilder()
      ->select('g')
      ->from('ClubUserBundle:Group', 'g')
      ->leftJoin('g.location','l')
      ->andWhere('g.group_type = :type')
      ->andWhere('(g.gender IS NULL OR g.gender=:gender)')
      ->andWhere('(g.min_age IS NULL OR g.min_age <= :min_age)')
      ->andWhere('(g.max_age IS NULL OR g.max_age >= :max_age)')
      ->andWhere('(g.active_member IS NULL OR g.active_member = :active_member)')
      ->andWhere('('.$location_str.' l.id IS NULL)')
      ->setParameter('type', 'dynamic')
      ->setParameter('gender', $user->getProfile()->getGender())
      ->setParameter('min_age', $user->getProfile()->getAge())
      ->setParameter('max_age', $user->getProfile()->getAge())
      ->setParameter('active_member', $active)
      ->getQuery()
      ->getResult();
  }

  private function getBySearchQuery(array $user = null, $sort = 'u.member_number', $active = true, $limit = null)
  {
    $qb = $this->getQueryBuilder();

    if (isset($user['id']) && $user['id'] != '') {
      $qb
        ->andWhere('u.id = :id')
        ->setParameter('id', $user['id']);
    } else {

      $user['query'] = isset($user['query']) ? $user['query'] : '';
      $qb
        ->andWhere('u.member_number = :number')
        ->orWhere("CONCAT(CONCAT(p.first_name,' '), p.last_name) LIKE :query")
        ->orderBy($sort, 'ASC')
        ->setParameter('number', $user['query'])
        ->setParameter('query', '%'.$user['query'].'%');

      if (isset($user['gender'])) {
        $qb->andWhere('p.gender = :gender')
          ->setParameter('gender', $user['gender']);
      }

      if ($active) $qb = $this->filterActive($qb, true);
      if ($limit) {
          $qb->setMaxResults($limit);
      }
    }

    return $qb;
  }

  public function getOneBySearch(array $user = null, $sort = 'u.member_number', $active = true)
  {
    return $this->getBySearchQuery($user, $sort, $active)
      ->getQuery()
      ->getOneOrNullResult();
  }

  public function getBySearch(array $user = null, $sort = 'u.member_number', $active = true, $limit = null)
  {
    return $this->getBySearchQuery($user, $sort, $active, $limit)
        ->getQuery()
        ->getResult();
  }

  public function getByAjax($query, $query_id)
  {
      if (strlen($query_id)) {
          return $this->_em->getRepository('ClubUserBundle:User')->find($query_id);
      } else {
          return $this->getBySearch(
              array('query' => $query),
              'u.member_number',
              false
          );
      }
  }

  public function getPaginator($results, $page)
  {
      $offset = ($page < 1) ? 1 : ($page-1)*$results;

      $login_time = new \DateTime();
      $i = new \DateInterval('P1Y');
      $login_time->sub($i);

      $query = $this->getQueryBuilder()
          ->andWhere('u.last_login_time > :login_time')
          ->orderBy('p.first_name')
          ->setParameter('login_time', $login_time)
          ->setFirstResult($offset)
          ->setMaxResults($results);

      return new \Doctrine\ORM\Tools\Pagination\Paginator($query);
  }

  public function getRecent($limit=10)
  {
      return $this->getQueryBuilder()
          ->setMaxResults($limit)
          ->orderBy('u.id', 'DESC')
          ->getQuery()
          ->getResult();
  }
}
