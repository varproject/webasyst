import React from 'react';
import classNames from 'classNames';
import { usePageUrl, useSvg } from '@scompiler/0003-product/.scompiler/hooks';

export interface NavMenuItem {
    icon?: string;
    title: string;
    link?: string;
    menu?: NavMenuItem[];
    badge?: {
        content: number;
        style: string;
    };
}

interface Props {
    items?: NavMenuItem[];
    level?: number;
}

function isChildActive(menuItem, currentUrl) {
    const fn = function(item) {
        if (item.menu) {
            for (const subitem of item.menu) {
                if (fn(subitem)) {
                    return true;
                }
            }
        }

        return item.link === currentUrl;
    }

    return fn(menuItem);
}

export default function NavMenu(props: Props) {
    const { items = [], level = 0 } = props;
    const isRoot = level === 0;
    const pageUrl = usePageUrl();
    const svg = useSvg();

    const rootClassName = classNames('sa-nav__menu', {
        'sa-nav__menu--root': isRoot,
        'sa-nav__menu--sub': !isRoot,
    });

    return (
        <ul className={rootClassName} data-sa-collapse-content={!isRoot ? '' : undefined}>
            {items.map((item, ids) => {
                const itemClassName = classNames('sa-nav__menu-item', {
                    'sa-nav__menu-item--active': item.link == pageUrl,
                    'sa-nav__menu-item--open': item.menu && isChildActive(item, pageUrl),
                    'sa-nav__menu-item--has-icon': item.icon && isRoot,
                });

                return (
                    <li
                        key={ids}
                        className={itemClassName}
                        data-sa-collapse-item={item.menu ? 'sa-nav__menu-item--open' : undefined}
                    >
                        <a
                            href={item.link || ''}
                            className="sa-nav__link"
                            data-sa-collapse-trigger={item.menu ? '' : undefined}
                        >
                            {new Array(level).fill(0).map((x, idx) => (
                                <span key={idx} className="sa-nav__menu-item-padding" />
                            ))}
                            {item.icon && isRoot && (
                                <span className="sa-nav__icon">{svg(item.icon)}</span>
                            )}

                            <span className="sa-nav__title">{item.title}</span>

                            {item.badge && (
                                <span className={`sa-nav__menu-item-badge badge badge-sa-pill badge-${item.badge.style}`}>
                                    {item.badge.content}
                                </span>
                            )}
                            {item.menu && (
                                <span className="sa-nav__arrow">
                                    {svg('stroyka/arrow-6x9')}
                                </span>
                            )}
                        </a>
                        {item.menu && <NavMenu items={item.menu} level={level + 1} />}
                    </li>
                );
            })}
        </ul>
    );
}
