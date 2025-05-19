/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export interface ConfigValues {
    [name: string]: string;
}

export class ConfigService {
    protected values: ConfigValues;

    constructor() {
        this.values = {};
        this.loadFromGlobal('userId');
        this.loadFromGlobal('urlsrc');
        this.loadFromGlobal('directEdit');
        this.loadFromGlobal('permissions');
        this.loadFromGlobal('instanceId');
    }

    loadFromGlobal(key: string): void {
        // @ts-ignore
        this.values[key] = window['officeonline_' + key];
    }

    update(key: string, value: string): void {
        this.values[key] = value;
    }

    get(key: string): string | undefined {
        if (typeof this.values[key] === 'undefined') {
            this.loadFromGlobal(key);
        }
        return this.values[key];
    }
}

const Config = new ConfigService();

export default Config;
