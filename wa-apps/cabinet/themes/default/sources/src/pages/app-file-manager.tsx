import React from 'react';
import Layout from '../components/Layout';
import App from '../components/App';
import PageHeader from "../components/PageHeader";
import { url } from "../components/utils";
import Image from "../components/Image";
import { useSvg } from "@scompiler/0003-product/.scompiler/hooks";
import classnames from "classnames";

type Section = {
    title?: string;
    items: {
        title: string;
        current?: boolean;
        icon?: string;
        color?: string;
        badge?: {
            style: string;
            content: string;
        };
    }[];
};

export default function() {
    const svg = useSvg();
    const attributes = [
        {name: 'Type', value: 'Compressed Archive'},
        {name: 'Size', value: '28 MB'},
        {name: 'Storage used', value: '28 MB'},
        {name: 'Location', value: 'My Drive'},
        {name: 'Owner', value: 'Veselovsky'},
        {name: 'Modified', value: '30 Apr 2021'},
        {name: 'Created', value: '8 Jan 2021'},
    ];

    const folders = [
        {name: 'Pictures', files: 275},
        {name: 'Documents', files: 12},
        {name: 'Programs', files: 3},
        {name: 'Videos', files: 48},
        {name: 'Downloads', files: 7},
        {name: 'Music', files: 378},
        {name: 'Favorites', files: 51},
        {name: 'Contacts', files: 192},
    ];

    const files = [
        {image: 'images/files/file-1.jpg', name: 'mountain-elbrus.jpg', type: 'jpg', size: '578.07 KB'},
        {image: 'images/files/file-2.jpg', name: 'engine.jpg', type: 'jpg', size: '895.69 KB'},
        {image: 'images/files/file-3.jpg', name: 'rusty-truck.jpg', type: 'jpg', size: '437.33 KB'},
        {image: 'images/files/file-4.jpg', name: 'wrench.jpg', type: 'jpg', size: '951.04 KB'},
        {image: 'images/files/file-5.jpg', name: 'panorama-of-moscow.jpg', type: 'jpg', size: '197.62 KB'},
        {icon: 'bootstrap/file-earmark-word-fill', name: 'diploma.docx', type: 'docx', size: '275.87 KB'},
        {icon: 'bootstrap/file-earmark-excel-fill', name: 'month-report.xlsx', type: 'xlsx', size: '24.30 KB'},
        {icon: 'bootstrap/file-earmark-pdf-fill', name: 'invoice-a972.pdf', type: 'pdf', size: '1.76 MB'},
        {icon: 'bootstrap/file-earmark-image-fill', name: 'pillars-of-creation.jpg', type: 'jpg', size: '793.64 KB'},
        {icon: 'bootstrap/file-earmark-text-fill', name: 'passwords.txt', type: 'txt', size: '284 B'},
        {icon: 'bootstrap/file-earmark-play-fill', name: 'green-mile.mkv', type: 'mkv', size: '15.43 GB'},
        {icon: 'bootstrap/file-earmark-music-fill', name: 'ppk-resurrection.mp3', type: 'mp3', size: '2.55 MB'},
        {icon: 'bootstrap/file-earmark-zip-fill', name: 'stroyka-vue.zip', type: 'zip', size: '2.10 MB'},
        {icon: 'bootstrap/file-earmark-zip-fill', name: 'stroyka-react.zip', type: 'zip', size: '4.13 MB'},
        {icon: 'bootstrap/file-earmark-zip-fill', name: 'stroyka-angular.zip', type: 'zip', size: '2.17 MB'},
        {icon: 'bootstrap/file-earmark-zip-fill', name: 'stroyka-html.zip', type: 'zip', size: '27.22 MB'},
    ];

    const sections: Section[] = [
        {
            items: [
                {title: 'My Drive', icon: 'feather/hard-drive', current: true},
                {title: 'Images', icon: 'feather/image'},
                {title: 'Shared with me', icon: 'feather/users'},
                {title: 'Recent', icon: 'feather/clock'},
                {title: 'Starred', icon: 'feather/star'},
                {title: 'Recycle Bin', icon: 'feather/trash-2'},
            ],
        },
        {
            title: 'Labels',
            items: [
                {title: 'Important', icon: 'stroyka/circle-20', color: '#fa3939'},
                {title: 'Vacation', icon: 'stroyka/circle-20', color: '#3562ff'},
                {title: 'Isabel Williams', icon: 'stroyka/circle-20', color: '#53a700'},
                {title: 'Jacob Lee', icon: 'stroyka/circle-20', color: '#8939c8'},
            ],
        },
    ];

    const foldersHtml = (
        <div className="sa-grid">
            <div className="sa-grid__body">
                {folders.map((folder, folderIdx) => (
                    <div className="sa-grid__item" key={folderIdx}>
                        <a href="#" className="sa-folder">
                            <div className="sa-folder__image">
                                {svg('bootstrap/folder-fill')}
                            </div>
                            <div className="sa-folder__info">
                                <div className="sa-folder__name">{folder.name}</div>
                                <div className="sa-folder__meta">{folder.files} files</div>
                            </div>
                        </a>
                    </div>
                ))}
                {[1,2,3,4,5,6,7,8,9].map(idx => (
                    <div key={idx} className="sa-grid__filler" role="presentation" />
                ))}
            </div>
        </div>
    );

    const filesHtml = (
        <div className="sa-grid">
            <div className="sa-grid__body">
                {files.map((file, fileIdx) => (
                    <div key={fileIdx} className="sa-grid__item">
                        <a className={`sa-file sa-file--type--${file.type}`} data-bs-toggle="offcanvas" href="#fileManagerDetails" role="button" aria-controls="fileManagerDetails">
                            <div className="sa-file__thumbnail sa-box">
                                <div className="sa-box__body">
                                    {!file.image && file.icon && (
                                        <div className="sa-box__container sa-file__icon">
                                            {svg(file.icon)}
                                        </div>
                                    )}
                                    {file.image && (
                                        <div className="sa-box__container sa-file__image">
                                            <Image
                                                src={file.image}
                                                size={[320, 180]}
                                                alt="Paradise Island"
                                            />
                                        </div>
                                    )}
                                </div>
                                <div className="sa-file__badge badge badge-sa-dark text-uppercase">
                                    {file.type}
                                </div>
                            </div>
                            <div className="sa-file__info">
                                <div className="sa-file__name">{file.name}</div>
                                <div className="sa-file__meta">{file.size}</div>
                            </div>
                        </a>
                    </div>
                ))}
                {[1,2,3,4,5,6,7,8,9].map(idx => (
                    <div key={idx} className="sa-grid__filler" role="presentation" />
                ))}
            </div>
        </div>
    );

    const sidebar = (
        <React.Fragment>
            <div className="p-4" data-simplebar="">
                <button type="button" className="btn btn-primary d-block w-100 mb-4">Upload File</button>

                <ul className="sa-nav sa-nav--card">
                    {sections.map((section, sectionIdx) => (
                        <li key={sectionIdx} className="sa-nav__section">
                            {section.title && (
                                <div className="sa-nav__section-title">
                                    <span>{section.title}</span>
                                </div>
                            )}

                            <ul className="sa-nav__menu">
                                {section.items.map((item, itemIdx) => (
                                    <li key={itemIdx} className={classnames('sa-nav__menu-item', {'sa-nav__menu-item--active': item.current, 'sa-nav__menu-item--has-icon': item.icon})}>
                                        <a href="#" className="sa-nav__link">
                                            {item.icon && (
                                                <span className="sa-nav__icon" style={{color: item.color}}>{svg(item.icon)}</span>
                                            )}

                                            <span className="sa-nav__title">{item.title}</span>

                                            {item.badge && (
                                                <span className={`sa-nav__badge badge badge-sa-pill badge-${item.badge.style}`}>
                                            {item.badge.content}
                                        </span>
                                            )}
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        </li>
                    ))}
                </ul>
            </div>
            <div className="p-4 pb-0 flex-grow-1 d-flex flex-column">
                <div className="flex-grow-1" />
                <div className="position-sticky bottom-0 pb-4">
                    <div className="fs-exact-14 mb-2">
                        <span className="fw-medium">254 GB</span> <span className="text-muted">of 500 GB used</span>
                    </div>

                    <div className="progress" style={{height: '8px', '--sa-progress--value': '25%'} as any}>
                        <div
                            className="progress-bar progress-bar-sa-primary"
                            role="progressbar"
                            aria-valuenow={25}
                            aria-valuemin={0}
                            aria-valuemax={100}
                        />
                    </div>

                    <button type="button" className="btn btn-secondary d-block w-100 mt-4">Buy Storage</button>
                </div>
            </div>
        </React.Fragment>
    );

    const content = (
        <div className="card flex-grow-1 mx-sm-0 mx-n4">
            <div className="p-4">
                <div className="row g-4">
                    <div className="col-auto sa-layout__filters-button">
                        <button className="btn btn-sa-muted btn-sa-icon fs-exact-16" data-sa-layout-sidebar-open="">
                            {svg('stroyka/filters-16')}
                        </button>
                    </div>
                    <div className="col">
                        <input
                            type="text"
                            placeholder="Start typing to search for files"
                            className="form-control form-control--search mx-auto"
                        />
                    </div>
                </div>
            </div>

            <div className="sa-divider" />

            <div className="p-md-5 p-4">
                <div className="">
                    <div className="fs-6 fw-medium mb-3">Folders</div>
                    {foldersHtml}
                </div>

                <div className="mt-5">
                    <div className="fs-6 fw-medium mb-3">Files</div>
                    {filesHtml}
                </div>
            </div>
        </div>
    );

    return (
        <Layout>
            <App>
                <div className="mx-xxl-3 px-4 px-sm-5">
                    <PageHeader
                        title="File Manager"
                        actions={[
                            <a key="new-folder" href="#" className="btn btn-secondary me-3">
                                New Folder
                            </a>,
                            <a key="new-file" href={url('product')} className="btn btn-primary">
                                New File
                            </a>,
                        ]}
                        breadcrumb={[
                            {title: 'Dashboard', url: url('dashboard')},
                            {title: 'File Manager', url: url('products-list')},
                        ]}
                    />
                </div>
                <div className="mx-xxl-3 px-4 px-sm-5 pb-6">
                    <div className="sa-layout">
                        <div className="sa-layout__backdrop" data-sa-layout-sidebar-close="" />
                        <div className="sa-layout__sidebar d-flex flex-column">
                            {sidebar}
                        </div>
                        <div className="sa-layout__content d-flex">
                            {content}
                        </div>
                    </div>
                </div>

                <div
                    className="offcanvas offcanvas-end offcanvas-sa-card"
                    tabIndex={-1}
                    id="fileManagerDetails"
                    aria-labelledby="fileManagerDetailsLabel"
                >
                    <div className="offcanvas-header py-3">
                        <div className="my-2">
                            <h5
                                className="offcanvas-title fs-exact-18"
                                id="fileManagerDetailsLabel"
                            >
                                stroyka-admin.jpg
                            </h5>
                            <div className="fs-exact-14 text-muted mt-1 mb-n1">Compressed ZIP folder</div>
                        </div>
                        <button
                            type="button"
                            className="sa-close sa-close--modal"
                            data-bs-dismiss="offcanvas"
                            aria-label="Close"
                        />
                    </div>
                    <div className="offcanvas-body" data-simplebar="">
                        <div className="border p-4 d-flex justify-content-center mb-4">
                            <div className="max-w-20x">
                                <Image src="images/products/product-7.jpg" size={16 * 20} className="w-100 h-auto" />
                            </div>
                        </div>

                        <div className="fs-exact-14 text-muted mb-2 pb-1">
                            Shared with:
                        </div>

                        <div className="mb-4">
                            <div className="sa-symbols-stack">
                                <div className="sa-symbols-stack__item sa-symbol sa-symbol--shape--circle">
                                    <Image src="images/customers/customer-9.jpg" size={32} />
                                </div>
                                <div className="sa-symbols-stack__item sa-symbol sa-symbol--shape--circle">
                                    <Image src="images/customers/customer-7.jpg" size={32} />
                                </div>
                                <div className="sa-symbols-stack__item sa-symbol sa-symbol--shape--circle">
                                    <Image src="images/customers/customer-11.jpg" size={32} />
                                </div>
                            </div>
                        </div>

                        <table className="w-100 fs-exact-14">
                            <tbody>
                                {attributes.map((attribute, attributeIdx) => (
                                    <tr key={attributeIdx}>
                                        <th className="py-1 fw-normal text-muted">{attribute.name}</th>
                                        <td className="py-1 ps-4">{attribute.value}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        <div className="sa-divider my-4" />

                        <label htmlFor="file-manager/file-description" className="form-label">Description</label>

                        <textarea placeholder="File description" className="form-control" rows={3} id="file-manager/file-description" />

                        <div className="sa-divider my-4" />

                        <div className="hstack gap-3">
                            <button type="button" className="btn btn-primary flex-grow-1">Download</button>

                            <button type="button" className="btn btn-secondary flex-grow-1">Delete</button>
                        </div>
                    </div>
                </div>
            </App>
        </Layout>
    );
}
