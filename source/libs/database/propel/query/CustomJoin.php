<?php
declare(strict_types=1);

/**
 * Data object to describe a join between two tables, for example
 * <pre>
 * table_a LEFT JOIN table_b ON table_a.id = table_b.a_id
 * </pre>
 *
 * @author     Francois Zaninotto (Propel)
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @author     Kaspars Jaudzems <kaspars.jaudzems@inbox.lv> (Propel)
 * @author     Frank Y. Kim <frank.kim@clearink.com> (Torque)
 * @author     John D. McNally <jmcnally@collab.net> (Torque)
 * @author     Brett McLaughlin <bmclaugh@algx.net> (Torque)
 * @author     Eric Dobbs <eric@dobbse.net> (Torque)
 * @author     Henning P. Schmiedehausen <hps@intermeta.de> (Torque)
 * @author     Sam Joseph <sam@neurogrid.com> (Torque)
 * @package    propel.runtime.query
 */
class CustomJoin extends Join
{
    protected $value_conditions;

    /**
     * Join condition definition.
     * Warning: doesn't support table aliases. Use the explicit methods to use aliases.
     *
     * @param string $left The left column of the join condition
     *                         (may contain an alias name)
     * @param $value
     * @param string $operator The comparison operator of the join condition, default Join::EQUAL
     * @internal param string $right The right column of the join condition
     *                         (may contain an alias name)
     */
    public function addRightValueCondition(string $left, $value, string $operator = self::EQUAL)
    {
        $i = count($this->left);

        if ($pos = strrpos($left, '.')) {
            [$this->leftTableName, $this->left[$i]] = explode('.', $left);
        } else {
            $this->left[$i] = $left;
        }
        $this->value_conditions[] = $i;
        $this->right[$i] = $value;
        $this->operator[$i] = $operator;
        $this->count++;
    }

    /**
     * Get the join clause for this Join.
     * If the join condition needs binding, uses the passed params array.
     *
     * @param array &$params
     *
     * @return string SQL join clause with join condition
     * @throws PropelException
     * @example
     * <code>
     * $join = new Join();
     * $join->addExplicitCondition('book', 'AUTHOR_ID', null, 'author', 'ID');
     * $params = array();
     * echo $j->getClause($params);
     * // 'LEFT JOIN author ON (book.AUTHOR_ID=author.ID)'
     * </code>
     */
    public function getClause(&$params): string
    {
        if (null === $this->joinCondition) {
            $conditions = [];
            for ($i = 0; $i < $this->count; $i++) {
                if ($this->value_conditions && in_array($i, $this->value_conditions)) {
                    $conditions [] = $this->getRightTableAliasOrName($i) . '.' . $this->left[$i] . $this->getOperator($i) . $this->right[$i];
                } else {
                    $conditions [] = $this->getLeftColumn($i) . $this->getOperator($i) . $this->getRightColumn($i);
                }
            }
            // bindValue(PDOStatement $stmt, $parameter, $value, ColumnMap $cMap, $position = null)
            $joinCondition = sprintf('(%s)', implode(' AND ', $conditions));
        } else {
            $joinCondition = '';
            $this->getJoinCondition()->appendPsTo($joinCondition, $params);
        }

        $rightTableName = $this->getRightTableWithAlias();

        if (null !== $this->db && $this->db->useQuoteIdentifier()) {
            $rightTableName = $this->db->quoteIdentifierTable($rightTableName);
        }

        return sprintf('%s %s ON %s',
            $this->getJoinType(),
            $rightTableName,
            $joinCondition
        );
    }

}
