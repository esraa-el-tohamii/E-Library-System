DROP TABLE Book;
DROP TABLE Author;
DROP TABLE Category;
DROP TABLE Borrowing;
DROP TABLE Member;
DROP TABLE Admin;
DROP TABLE BookCopy;
DROP TABLE Permission;
DROP TABLE Have;
DROP TABLE Book_Keyword;
DROP TABLE MemberPhone;

CREATE TABLE Book(BOOKID INT PRIMARY KEY,
Title VARCHAR(30) NOT NULL,
ISNB VARCHAR(30),
CatID INT);

CREATE TABLE Author(AuthorID INT PRIMARY KEY,
Name VARCHAR(50) NOT NULL,
Bithdate DATE);

CREATE TABLE Category(CategoryID INT PRIMARY KEY,
CategoryName VARCHAR(50));

CREATE TABLE Borrowing(BorrowID INT PRIMARY KEY,
BorrowDate DATE,
ReturnDate DATE,
MemberID INT,
CopyID INT,
AdminID INT);

CREATE TABLE Member(MemberID INT PRIMARY KEY,
Name VARCHAR(50) NOT NULL,
Email VARCHAR(30),
Password VARCHAR(100),
Birthdate DATE);

CREATE TABLE Admin(AdminID INT PRIMARY KEY,
Name VARCHAR(50) NOT NULL,
Email VARCHAR(30),
Password VARCHAR(50),
Role VARCHAR(50),
CreatedAt DATETIME,
LastLogin DATETIME,
ManagerID INT,
PerID INT);

CREATE TABLE Permission(PermissionID INT PRIMARY KEY,
PermissionName VARCHAR(50));

CREATE TABLE BookCopy(BookID INT,
CopyID INT,
Status VARCHAR(50),
Count INT);

CREATE TABLE Have(BookID INT,
AuthID INT,
ReleasedDate DATE);

CREATE TABLE Book_Keyword(BookID INT,
Keywords VARCHAR(100));

CREATE TABLE MemberPhone(MemberID INT,
Phone VARCHAR(30));
