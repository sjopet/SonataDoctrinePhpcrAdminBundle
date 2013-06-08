<?php

namespace Sonata\DoctrinePHPCRAdminBundle\Admin;

use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\DoctrinePHPCRAdminBundle\Admin\Admin;
use Sonata\DoctrinePHPCRAdminBundle\Datagrid\ProxyQuery;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ODM\PHPCR\DocumentManager;

class GroupListManager extends Admin
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->baseRouteName = 'admin_bundle_group_page_content';
        $this->baseRoutePattern = '/bundle/block/group/manager';
    }

    /**
     * @param \Sonata\AdminBundle\Datagrid\DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id', 'doctrine_phpcr_string', array('label' => 'ID'))
            ->add('name',  'doctrine_phpcr_nodename')
            ->add('type', 'doctrine_phpcr_string', array('label' => 'Type'))
        ;
    }

    /**
     * @param \Sonata\AdminBundle\Datagrid\ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $adminField = new SubAdminFieldDescription();
        $adminField->setFieldName('type');

        $listMapper
            ->addIdentifier('name', 'text')
            ->add('id', 'text')
            ->add('path', 'text') // @todo combine these fields
            ->add($adminField, 'text')
        ;
    }

    /**
     * @param string $context
     * @return ProxyQuery
     */
    public function createQuery($context = 'list')
    {
        $dm = $this->getModelManager()->getDocumentManager();
        /** @var \Doctrine\ODM\PHPCR\Query\QueryBuilder $qb */
        $qb = $dm->createQueryBuilder();
        $query = new ProxyQuery($qb);
        $query->setDocumentManager($dm);
        $qb->nodeType('nt:unstructured');

        foreach ($this->getSubClasses() as $class => $admin) {
            $exp = $qb->expr()->eq('phpcr:class', $class);
            $qb->orWhere($exp);
        }
        return $query;
    }

    /**
     * @param string $class
     * @return AdminInterface
     */
    public function getSubAdmin($class)
    {
        if(is_object($class)){
            $class = \Doctrine\Common\Util\ClassUtils::getRealClass(get_class($class));
        }

        return $this->getConfigurationPool()->getAdminByClass($class);
    }

    /**
     * Because of the structure of the subclasses array
     * We need to change this function slightly
     *
     * @param  string $name The name of the sub class
     * @return string the subclass
     */
    protected function getSubClass($name)
    {
        if ($this->hasSubClass($name)) {
            return $name;
        }

        return null;
    }
}
