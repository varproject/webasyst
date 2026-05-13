import React from 'react';
import classNames from 'classNames';
import SidebarLogo from './SidebarLogo';
import Nav from './Nav';
import { url } from "./utils";

interface Props {
    className?: string;
}

export default function({className = ''}: Props) {
    return (
        <div className={classNames('sa-sidebar', className)}>
            <div className="sa-sidebar__header">
                <a className="sa-sidebar__logo" href={url('dashboard')}>
                    <SidebarLogo />
                </a>
            </div>
            <div className="sa-sidebar__body" data-simplebar="">
                <Nav />
            </div>
        </div>
    );
}
