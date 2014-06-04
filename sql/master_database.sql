CREATE TABLE DLM (
    DLMID               int UNSIGNED NOT NULL AUTO_INCREMENT,
    Name                varchar(80),
    ClassName           varchar(80),
    DataDescriptionXML  text,
    PRIMARY KEY (DLMID)
);

CREATE TABLE DLMConfig (
    DLMID               int UNSIGNED,
    Name                varchar(80),
    Value               varchar(80),
    PRIMARY KEY (DLMID, Name)
);

CREATE TABLE DLMTreeTable (
    DLMTreeTableID      int UNSIGNED NOT NULL AUTO_INCREMENT,
    DLMID               int UNSIGNED,
    Name                varchar(80),
    PRIMARY KEY (DLMTreeTableID),
    INDEX (DLMID)
);

CREATE TABLE DLMTreeColumn (
    DLMTreeColumnID     varchar(80) UNIQUE NOT NULL,
    DLMTreeTableID      int UNSIGNED,
    Name                varchar(80),
    Type                enum('Numeric','String','Text'),
    IsKey               enum('Yes','No'),
    PRIMARY KEY (DLMTreeColumnID),
    INDEX (DLMTreeTableID)
);

# WARNING: It is possible to INSERT a Tree Link across different DLMs, however this is
# not part of the design, that is what connectors are for. The database will
# allow it, but it will not work in the code, and my cause errors.
CREATE TABLE DLMTreeLink (
    DLMTreeColumnID         varchar(80),
    DLMTreeColumnIDForeign  varchar(80),
    Type	            enum('OneToOne','OneToMany','ManyToMany'),
    PRIMARY KEY (DLMTreeColumnID, DLMTreeColumnIDForeign),
    INDEX (DLMTreeColumnID),
    INDEX (DLMTreeColumnIDForeign)  
);


CREATE TABLE DLMConnector (
    ConnectorID             int UNSIGNED NOT NULL AUTO_INCREMENT,
    ConnectorName           varchar(80),
    DLMTreeColumnIDPrimary  varchar(80),
    DLMTreeColumnIDForeign  varchar(80),
    Type	            enum('OneToOne','OneToMany','ManyToMany'),
    KeyType                 enum('BothInt','BothVarchar','IntToVarchar','VarcharToInt'),
    PRIMARY KEY (ConnectorID),
    INDEX (DLMTreeColumnIDPrimary),
    INDEX (DLMTreeColumnIDForeign)
);

CREATE TABLE DLMConnectorOneToOneBothInt (
    ConnectorID         int UNSIGNED,
    PrimaryKey          int,
    ForeignKey          int,
    PRIMARY KEY (ConnectorID, PrimaryKey),
    INDEX (ConnectorID),
    INDEX (PrimaryKey),
    INDEX (ForeignKey)
);

CREATE TABLE DLMConnectorOneToManyBothInt (
    ConnectorID         int UNSIGNED,
    PrimaryKey          int,
    ForeignKey          int,
    PRIMARY KEY (ConnectorID, ForeignKey),
    INDEX (ConnectorID),
    INDEX (PrimaryKey),
    INDEX (ForeignKey)
);

CREATE TABLE DLMConnectorManyToManyBothInt (
    ConnectorID         int UNSIGNED,
    PrimaryKey          int,
    ForeignKey          int,
    PRIMARY KEY (ConnectorID, PrimaryKey, ForeignKey),
    INDEX (ConnectorID),
    INDEX (PrimaryKey),
    INDEX (ForeignKey)
);

CREATE TABLE DLMConnectorOneToOneBothVarchar (
    ConnectorID         int UNSIGNED,
    PrimaryKey          varchar(80),
    ForeignKey          varchar(80),
    PRIMARY KEY (ConnectorID, PrimaryKey),
    INDEX (ConnectorID),
    INDEX (PrimaryKey),
    INDEX (ForeignKey)
);

CREATE TABLE DLMConnectorOneToManyBothVarchar (
    ConnectorID         int UNSIGNED,
    PrimaryKey          varchar(80),
    ForeignKey          varchar(80),
    PRIMARY KEY (ConnectorID, ForeignKey),
    INDEX (ConnectorID),
    INDEX (PrimaryKey),
    INDEX (ForeignKey)
);

CREATE TABLE DLMConnectorManyToManyBothVarchar (
    ConnectorID         int UNSIGNED,
    PrimaryKey          varchar(80),
    ForeignKey          varchar(80),
    PRIMARY KEY (ConnectorID, PrimaryKey, ForeignKey),
    INDEX (ConnectorID),
    INDEX (PrimaryKey),
    INDEX (ForeignKey)
);

CREATE TABLE DLMConnectorOneToOneIntToVarchar (
    ConnectorID         int UNSIGNED,
    PrimaryKey          int,
    ForeignKey          varchar(80),
    PRIMARY KEY (ConnectorID, PrimaryKey),
    INDEX (ConnectorID),
    INDEX (PrimaryKey),
    INDEX (ForeignKey)
);

CREATE TABLE DLMConnectorOneToManyIntToVarchar (
    ConnectorID         int UNSIGNED,
    PrimaryKey          int,
    ForeignKey          varchar(80),
    PRIMARY KEY (ConnectorID, ForeignKey),
    INDEX (ConnectorID),
    INDEX (PrimaryKey),
    INDEX (ForeignKey)
);

CREATE TABLE DLMConnectorManyToManyIntToVarchar (
    ConnectorID         int UNSIGNED,
    PrimaryKey          int,
    ForeignKey          varchar(80),
    PRIMARY KEY (ConnectorID, PrimaryKey, ForeignKey),
    INDEX (ConnectorID),
    INDEX (PrimaryKey),
    INDEX (ForeignKey)
);

CREATE TABLE DLMConnectorOneToOneVarcharToInt (
    ConnectorID         int UNSIGNED,
    PrimaryKey          varchar(80),
    ForeignKey          int,
    PRIMARY KEY (ConnectorID, PrimaryKey),
    INDEX (ConnectorID),
    INDEX (PrimaryKey),
    INDEX (ForeignKey)
);

CREATE TABLE DLMConnectorOneToManyVarcharToInt (
    ConnectorID         int UNSIGNED,
    PrimaryKey          varchar(80),
    ForeignKey          int,
    PRIMARY KEY (ConnectorID, ForeignKey),
    INDEX (ConnectorID),
    INDEX (PrimaryKey),
    INDEX (ForeignKey)
);

CREATE TABLE DLMConnectorManyToManyVarcharToInt (
    ConnectorID         int UNSIGNED,
    PrimaryKey          varchar(80),
    ForeignKey          int,
    PRIMARY KEY (ConnectorID, PrimaryKey, ForeignKey),
    INDEX (ConnectorID),
    INDEX (PrimaryKey),
    INDEX (ForeignKey)
);

# Not sure about the SyncType, because two way would require a tracking table.
CREATE TABLE DLMSync (
    DLMSyncID           int UNSIGNED NOT NULL AUTO_INCREMENT,
    SyncName            varchar(80),
    DLMID               int UNSIGNED,
    DataName            varchar(80),
    ForeignDLMID        int UNSIGNED,
    ForeignDataName     varchar(80),
    SyncType            enum('One Way','Two Way'),
    PRIMARY KEY (DLMSyncID),
    INDEX (DLMID),
    INDEX (ForeignDLMID)
);


# Permalink is a SEO Friendly Name - http://searchengineland.com/seo-friendly-url-syntax-practices-134218
# The page id will not be passed as part of the URL, need to add a controller for matching up the PageID.
# This page is loaded as part of the page.php template file, much like the way WordPress loads a post.
# It dynamically groups pages within the admin interface using the permalink, based on the names put inbetween the slashs.
# Example: /account/edit/ and /account/view/ are listed together under the "account" tree.
#
# Thinking about doing a mod_rewrite of everything the starts with /app/ so,
# /app/account/edit/ becomes index.php/account/edit/
# not sure how this would effect passing name/value pairs on the URL like, /app/account/edit/?id=1234
#
# The /app/ will be added automatically. It specifies the type of controller.  I've thought of /wiki/ and /post/ (blog).
CREATE TABLE Page (
    PageID              int UNSIGNED NOT NULL AUTO_INCREMENT,
    Title               varchar(80),
    Permalink           varchar(80),
    Content             text,
    ACLType             enum('Open To World','Open To All Logged In Users','Follows User Roles'),
    Controller          varchar(80),
    PRIMARY KEY (PageID),
    KEY (Permalink, Controller),
    INDEX (Permalink),
    INDEX (Controller)
);

INSERT INTO Page VALUES (NULL,'Test','/test/','<WebPB><WebPBHTML><HTML><![CDATA[<h1>Testing!</h1>]]></HTML></WebPBHTML></WebPB>','Open To World','app');
INSERT INTO Page VALUES (NULL,'Test','/test/no_class/','<WebPB><WebPBHTML><HTML><![CDATA[<h1>No Class Testing!</h1>]]></HTML></WebPBHTML><WebPBNoClass><HTML><![CDATA[<h1>Testing!</h1>]]></HTML></WebPBNoClass></WebPB>','Open To World','app');

# Any user/role combo in table will have access to that page.
CREATE TABLE PageACL (
    UserRoleID          smallint UNSIGNED,
    PageID              int UNSIGNED,
    PRIMARY KEY (UserRoleID, PageID)
);

CREATE TABLE UserRole (
    UserRoleID          smallint UNSIGNED NOT NULL AUTO_INCREMENT,
    RoleName            varchar(80),
    PRIMARY KEY (UserRoleID)
);

# This links a user to a specific role type
# Each user can have multiple Roles
CREATE TABLE UserToUserRole (
    UserID              int UNSIGNED,
    UserRoleID          smallint UNSIGNED,
    PRIMARY KEY (UserID, UserRoleID)
);

# A user is of the application, they access pages to add an update
# data over the DLMs.
CREATE TABLE Users (
    UserID              int UNSIGNED NOT NULL AUTO_INCREMENT,
    Username            varchar(80),
    PasswordHash        varchar(80),
    Name                varchar(80),
    Email               varchar(80),
    PRIMARY KEY (UserID)
);

# An admin accesses the configureation part of the application,
# they create pages, setup DLMs, add Users, edit ACL, create menus, etc.
CREATE TABLE Admins (
    AdminID             tinyint UNSIGNED NOT NULL AUTO_INCREMENT,
    Username            varchar(80),
    PasswordHash        varchar(80),
    Name                varchar(80),
    Email               varchar(80),
    PRIMARY KEY (AdminID)
);





<WebDataDescription>
    <Customer type="table">
        <CustomerID type="numeric">
            <Link>
                <Type>OneToMany</Type>
                <Table>Subscription</Table>
                <Column>CustomerID</Column>
            </Link>
            <Link>
                <Type>OneToMany</Type>
                <Table>Payment</Table>
                <Column>CustomerID</Column>
            </Link>
        </CustomerID>
        <Name type="string"/>
        <Phone type="numeric"/>
        <Email type="string"/>
        <Note type="text"/>
        <AddressID type="numeric">
            <Link>
                <Type>OneToOne</Type>
                <Table>Address</Table>
                <Column>AddressID</Column>
            </Link>
        </AddressID>
    </Customer>
    <Address type="table">
        <AddressID type="numeric"/>
        <AddressOne type="string"/>
        <AddressTwo type="string"/>
        <City type="string"/>
        <State type="string"/>
        <Zip type="numeric"/>
    </Address>
    <Subscription type="table">
        <SubscriptionID type="numeric"/>
        <CustomerID type="numeric"/>
        <Description type="string"/>
        <Cost type="numeric"/>
    </Subscription>
    <Payment type="table">
        <PaymentID type="numeric"/>
        <CustomerID type="numeric"/>
        <Date type="string"/>
        <Amount type="numeric"/>
    </Subscription>
</WebDataDescription>




# Created using the Reveal Command 
# Not sure yet if this table should have DataTypeID
CREATE TABLE DLMLinkingTree (

	DataTypeID		int UNSIGNED, 

);

# DataTypeID is a UUID(), this is so users are free to 
# create their own data types and they can live along side
# the standard ones.
CREATE TABLE DataType (
	DataTypeID		varchar(36),
	Name				varchar(80),
	PHPClass			varchar(80),
	PRIMARY KEY		(DataTypeID)
);

CREATE TABLE RecordFieldSyncOnCreate (

);

CREATE TABLE RecordFieldSync (

);
