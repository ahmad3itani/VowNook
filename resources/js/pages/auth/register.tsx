import { Form, Head } from '@inertiajs/react';
import { ClipboardList, Heart, Store } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

type Props = {
    passwordRules: string;
};

type AccountType = 'couple' | 'vendor' | 'planner';

export default function Register({ passwordRules }: Props) {
    const [accountType, setAccountType] = useState<AccountType>(() => {
        if (typeof window !== 'undefined') {
            const type = new URLSearchParams(window.location.search).get('type');
            if (type === 'vendor' || type === 'planner') return type;
        }
        return 'couple';
    });

    // Pre-fill the email when arriving from a collaboration invite link.
    const invitedEmail =
        typeof window !== 'undefined'
            ? (new URLSearchParams(window.location.search).get('email') ?? '')
            : '';

    // Carry a referral code through registration (?ref=CODE).
    const referralCode =
        typeof window !== 'undefined'
            ? (new URLSearchParams(window.location.search).get('ref') ?? '')
            : '';

    return (
        <>
            <Head title="Register" />
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            {/* Account-type picker */}
                            <input type="hidden" name="account_type" value={accountType} />
                            {referralCode && <input type="hidden" name="ref" value={referralCode} />}
                            <div className="grid grid-cols-3 gap-2">
                                <button
                                    type="button"
                                    onClick={() => setAccountType('couple')}
                                    className={`flex flex-col items-center gap-1.5 rounded-lg border p-3 text-center transition-colors ${
                                        accountType === 'couple'
                                            ? 'border-[#775a19] bg-[#775a19]/5 ring-1 ring-[#775a19]'
                                            : 'border-border hover:bg-muted'
                                    }`}
                                >
                                    <Heart className={`size-5 ${accountType === 'couple' ? 'text-[#775a19]' : 'text-muted-foreground'}`} />
                                    <span className="text-sm font-medium">Couple</span>
                                    <span className="text-xs text-muted-foreground">Planning our wedding</span>
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setAccountType('vendor')}
                                    className={`flex flex-col items-center gap-1.5 rounded-lg border p-3 text-center transition-colors ${
                                        accountType === 'vendor'
                                            ? 'border-[#775a19] bg-[#775a19]/5 ring-1 ring-[#775a19]'
                                            : 'border-border hover:bg-muted'
                                    }`}
                                >
                                    <Store className={`size-5 ${accountType === 'vendor' ? 'text-[#775a19]' : 'text-muted-foreground'}`} />
                                    <span className="text-sm font-medium">Vendor</span>
                                    <span className="text-xs text-muted-foreground">Offering services</span>
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setAccountType('planner')}
                                    className={`flex flex-col items-center gap-1.5 rounded-lg border p-3 text-center transition-colors ${
                                        accountType === 'planner'
                                            ? 'border-[#775a19] bg-[#775a19]/5 ring-1 ring-[#775a19]'
                                            : 'border-border hover:bg-muted'
                                    }`}
                                >
                                    <ClipboardList className={`size-5 ${accountType === 'planner' ? 'text-[#775a19]' : 'text-muted-foreground'}`} />
                                    <span className="text-sm font-medium">Planner</span>
                                    <span className="text-xs text-muted-foreground">Managing clients</span>
                                </button>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="name">
                                    {accountType === 'couple' ? 'Name' : 'Business name'}
                                </Label>
                                <Input
                                    id="name"
                                    type="text"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="name"
                                    name="name"
                                    placeholder="Full name"
                                />
                                <InputError
                                    message={errors.name}
                                    className="mt-2"
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    tabIndex={2}
                                    autoComplete="email"
                                    name="email"
                                    defaultValue={invitedEmail}
                                    placeholder="email@example.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">Password</Label>
                                <PasswordInput
                                    id="password"
                                    required
                                    tabIndex={3}
                                    autoComplete="new-password"
                                    name="password"
                                    placeholder="Password"
                                    passwordrules={passwordRules}
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    Confirm password
                                </Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    required
                                    tabIndex={4}
                                    autoComplete="new-password"
                                    name="password_confirmation"
                                    placeholder="Confirm password"
                                    passwordrules={passwordRules}
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>

                            <Button
                                type="submit"
                                className="mt-2 w-full"
                                tabIndex={5}
                                data-test="register-user-button"
                            >
                                {processing && <Spinner />}
                                Create account
                            </Button>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            Already have an account?{' '}
                            <TextLink href={login()} tabIndex={6}>
                                Log in
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

Register.layout = {
    title: 'Create an account',
    description: 'Enter your details below to create your account',
};
