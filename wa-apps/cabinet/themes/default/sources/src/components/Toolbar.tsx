import React from 'react';
import classNames from 'classnames';
import { useSvg } from '@scompiler/0003-product/.scompiler/hooks';
import Search from './Search';
import languages from '../data/languages.json';
import notifications from '../data/notifications.json';
import Image from "./Image";
import { url } from "./utils";

interface Props {
    className?: string;
}

export default function(props: Props) {
    const { className = '' } = props;
    const svg = useSvg();
    const searchShown = false;

    const rootClassName = classNames('sa-toolbar', {
        'sa-toolbar--search-shown': searchShown,
        'sa-toolbar--search-hidden': !searchShown,
    }, className);

    return (
        <div className={rootClassName}>
            <div className="sa-toolbar__body">
                <div className="sa-toolbar__item">
                    <button className="sa-toolbar__button" type="button" aria-label="Menu" data-sa-toggle-sidebar="">
                        {svg('stroyka/hamburger-20')}
                    </button>
                </div>

                <div className="sa-toolbar__item sa-toolbar__item--search">
                    <Search />
                </div>

                <div className="mx-auto" />

                <div className="sa-toolbar__item d-sm-none">
                    <button className="sa-toolbar__button" type="button" aria-label="Show search" data-sa-action="show-search">
                        {svg('stroyka/magnifier-16')}
                    </button>
                </div>

                <div className="sa-toolbar__item dropdown">
                    <button className="sa-toolbar__button" type="button" id="dropdownMenuButton3" data-bs-toggle="dropdown" data-bs-reference="parent" data-bs-offset="0,1" aria-expanded="false">
                        <Image src="vendor/flag-icons/24/DE.png" className="sa-language-icon" />
                    </button>
                    <ul className="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton3">
                        {languages.map((language, idx) => (
                            <li key={idx}>
                                <a className="dropdown-item d-flex align-items-center" href="#">
                                    <Image src={language.icon} className="sa-language-icon me-3" />
                                    <span className="ps-2">{language.name}</span>
                                </a>
                            </li>
                        ))}
                    </ul>
                </div>

                <div className="sa-toolbar__item dropdown">
                    <button className="sa-toolbar__button" type="button" id="dropdownMenuButton2" data-bs-toggle="dropdown" data-bs-reference="parent" data-bs-offset="0,1" aria-expanded="false">
                        {svg('stroyka/bell-16')}
                        <span className="sa-toolbar__button-indicator">3</span>
                    </button>
                    <div className="dropdown-menu dropdown-menu-end py-0" aria-labelledby="dropdownMenuButton2">
                        <div className="sa-notifications">
                            <div className="sa-notifications__header">
                                <div className="sa-notifications__header-title">Notifications</div>
                                <a className="sa-notifications__header-action" href="">Mark All as Read</a>
                            </div>
                            <ul className="sa-notifications__list">
                                {notifications.map((notification, idx) => (
                                    <li key={idx} className="sa-notifications__item">
                                        <a href="#" className="sa-notifications__item-button">
                                            <div className="sa-notifications__item-icon">
                                                <div className={classNames('sa-symbol sa-symbol--shape--rounded', notification.style && `sa-symbol--style--${notification.style}` )}>
                                                    <div className="sa-symbol__icon">
                                                        {svg(notification.icon)}
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="sa-notifications__item-body">
                                                <div className="sa-notifications__item-title sa-notifications__item-title--nowrap">
                                                    {notification.title}
                                                </div>
                                                <div className="sa-notifications__item-subtitle sa-notifications__item-subtitle--nowrap">
                                                    {notification.subtitle}
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                ))}
                            </ul>
                            <div className="sa-notifications__footer">
                                <a className="sa-notifications__footer-action" href="">See all 15 notifications</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="dropdown sa-toolbar__item">
                    <button className="sa-toolbar-user" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" data-bs-offset="0,1" aria-expanded="false">
                        <span className="sa-toolbar-user__avatar sa-symbol sa-symbol--shape--rounded">
                            <Image src="images/customers/customer-4.jpg" size={32 * 2} />
                        </span>
                        <span className="sa-toolbar-user__info">
                            <span className="sa-toolbar-user__title">Konstantin Veselovsky</span>
                            <span className="sa-toolbar-user__subtitle">stroyka@example.com</span>
                        </span>
                    </button>

                    <ul className="dropdown-menu w-100" aria-labelledby="dropdownMenuButton">
                        <li><a className="dropdown-item" href="#">Profile</a></li>
                        <li><a className="dropdown-item" href={url('inbox-list')}>Inbox</a></li>
                        <li><a className="dropdown-item" href={url('settings-toc')}>Settings</a></li>
                        <li><hr className="dropdown-divider" /></li>
                        <li><a className="dropdown-item" href={url('auth/sign-in')}>Sign Out</a></li>
                    </ul>
                </div>
            </div>
            <div className="sa-toolbar__shadow" />
        </div>
    );
}
