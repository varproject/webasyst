import React, { PropsWithChildren } from 'react';
import Sidebar from './Sidebar';
import classNames from 'classnames';
import Toolbar from './Toolbar';
import Comment from '@scompiler/0003-product/.scompiler/components/Comment';
import meta from '../data/meta.json';

type Props = PropsWithChildren<{
    className?: string;
    bodyClassName?: string;
    showFooter?: boolean;
}>;

export default function(props: Props) {
    const { children, className, bodyClassName = '', showFooter = true } = props;

    const desktopSidebarShown = true;
    const mobileSidebarShown = false;
    const toolbarStatic = false;

    const rootClassName = classNames('sa-app', {
        'sa-app--desktop-sidebar-shown': desktopSidebarShown,
        'sa-app--desktop-sidebar-hidden': !desktopSidebarShown,
        'sa-app--mobile-sidebar-shown': mobileSidebarShown,
        'sa-app--mobile-sidebar-hidden': !mobileSidebarShown,
        'sa-app--toolbar-static': toolbarStatic,
        'sa-app--toolbar-fixed': !toolbarStatic,
    }, className);

    return (
        <>
            <Comment value="sa-app" />
            <div className={rootClassName}>
                <Comment value="sa-app__sidebar" />
                <div className="sa-app__sidebar">
                    <Sidebar />

                    <div className="sa-app__sidebar-shadow" />
                    <div className="sa-app__sidebar-backdrop" data-sa-close-sidebar="" />
                </div>
                <Comment value="sa-app__sidebar / end" />

                <Comment value="sa-app__content" />
                <div className="sa-app__content">
                    <Comment value="sa-app__toolbar" />
                    <Toolbar className="sa-app__toolbar" />
                    <Comment value="sa-app__toolbar / end" />

                    <Comment value="sa-app__body" />
                    <div id="top" className={classNames('sa-app__body', bodyClassName)}>
                        {children}
                    </div>
                    <Comment value="sa-app__body / end" />

                    {showFooter && (
                        <>
                            <Comment value="sa-app__footer" />
                            <div className="sa-app__footer d-block d-md-flex">
                                <Comment value="copyright" />
                                {meta.theme.name} © 2021
                                <div className="m-auto" />
                                <div>
                                    Powered by HTML — Design by <a href={meta.author.profileUrl}>{meta.author.name}</a>
                                </div>
                                <Comment value="copyright / end" />
                            </div>
                            <Comment value="sa-app__footer / end" />
                        </>
                    )}
                </div>
                <Comment value="sa-app__content / end" />

                <Comment value="sa-app__toasts" />
                <div className="sa-app__toasts toast-container bottom-0 end-0" />
                <Comment value="sa-app__toasts / end" />
            </div>
            <Comment value="sa-app / end" />
        </>
    );
}
