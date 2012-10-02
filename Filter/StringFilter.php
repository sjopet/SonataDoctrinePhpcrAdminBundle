<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrinePHPCRAdminBundle\Filter;

use Sonata\DoctrinePHPCRAdminBundle\Form\Type\Filter\ChoiceType;
use Sonata\DoctrinePHPCRAdminBundle\Datagrid\ProxyQuery;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;

use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as Constants;

class StringFilter extends Filter
{
    /**
     * Applies a constraint to the query
     *
     * @param ProxyQueryInterface $queryBuilder
     * @param string $alias has no effect
     * @param string $field field uhere to apply the constraint
     * @param array $data determines the constraint
     * @return
     */
    public function filter(ProxyQueryInterface $queryBuilder, $alias, $field, $data)
    {
        if (!$data || !is_array($data) || !array_key_exists('value', $data)) {
            return;
        }

        $data['value'] = trim($data['value']);
        $data['type'] = !isset($data['type']) ?  ChoiceType::TYPE_CONTAINS : $data['type'];

        if (strlen($data['value']) == 0) {
            return;
        }

        $qf = $queryBuilder->getQueryObjectModelFactory();

        switch ($data['type']) {
        case ChoiceType::TYPE_EQUAL:
            $constraint = $qf->comparison(
                $qf->lowerCase($qf->propertyValue($field)),
                Constants::JCR_OPERATOR_EQUAL_TO,
                $qf->literal(mb_strtolower($data['value']))
            );
            break;
        case ChoiceType::TYPE_NOT_CONTAINS:
            $constraint = $qf->fulltextSearch($field, "* -".$data['value'], '['.$queryBuilder->getNodeType().']');
            break;
        case ChoiceType::TYPE_CONTAINS:
            $constraint = $qf->comparison(
                $qf->lowerCase($qf->propertyValue($field)),
                Constants::JCR_OPERATOR_LIKE,
                $qf->literal('%' . mb_strtolower($data['value']) . '%')
            );
            break;
        case ChoiceType::TYPE_CONTAINS_WORDS:
        default:
            $constraint = $qf->fulltextSearch($field, $data['value'], '['.$queryBuilder->getNodeType().']');

        }
        $queryBuilder->andWhere($constraint);

    }

    /**
     * @return array
     */
    public function getDefaultOptions()
    {
        return array(
            'format'   => '%%%s%%'
        );
    }

    public function getRenderSettings()
    {
        return array('doctrine_phpcr_type_filter_choice', array(
            'field_type'    => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'label'         => $this->getLabel()
        ));
    }
}
